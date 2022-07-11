<?php

namespace Framework\Serializer;

use App\App;
use App\Services\PostService;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Framework\Serializer\Converter\ISerializerDateTimeConverter;
use Framework\Serializer\Exception\ConverterNotDefined;
use Framework\Serializer\Exception\DuplicateConverterException;
use Framework\Serializer\Exception\InvalidBehaviorException;
use Framework\Serializer\Exception\InvalidConverterException;
use Framework\Serializer\Exception\InvalidTargetClassException;
use Framework\Serializer\Exception\MixedUpVariantException;
use Framework\Serializer\Exception\SerializationNotImplementedException;
use Framework\Serializer\Exception\SerializerException;
use Framework\Serializer\Exception\UnrecognizedFieldConfigurationException;
use Framework\Serializer\Exception\VariantNotSpecifiedException;
use Framework\Serializer\FieldBehaviors\ChangeNameBehavior;
use Framework\Serializer\FieldBehaviors\ConvertDateBehavior;
use Framework\Serializer\FieldBehaviors\GeneratedValueBehavior;
use Framework\Serializer\FieldBehaviors\ModifyValueBehavior;
use Framework\Serializer\FieldBehaviors\SerializeBehavior;
use ReflectionClass;
use ReflectionObject;

class SerializerManager
{
    private App $app;

    private array $serializers = [];

    private ?ISerializerDateTimeConverter $dateTimeConverter = null;

    /**
     * @param App $app
     * @param string[] $converters
     * @throws SerializerException
     */
    public function __construct(App $app, array $converters)
    {
        $this->app = $app;

        $this->autoMapConverters($converters, [
            ISerializerDateTimeConverter::class => 'dateTimeConverter'
        ]);
    }

    /**
     * @param string[] $converters
     * @param array $fieldMap
     * @return void
     * @throws SerializerException
     * @noinspection PhpSameParameterValueInspection
     */
    private function autoMapConverters(array $converters, array $fieldMap): void {
        foreach ($converters as $converterClass) {
            $instance = new $converterClass();
            $reflected = new ReflectionObject($instance);

            $field = null;
            foreach ($reflected->getInterfaces() as $interface) {
                $interfaceFQN = $interface->getName();

                if (array_key_exists($interfaceFQN, $fieldMap)) {
                    $field = $fieldMap[$interfaceFQN];

                    if ($this->$field !== null) {
                        throw new DuplicateConverterException(get_class($this->$field), get_class($instance));
                    }

                    $this->app->injectServicesIntoInstance($instance);

                    $this->$field = $instance;
                    break;
                }
            }

            if ($field === null) {
                throw new InvalidConverterException($converterClass);
            }
        }
    }

    /**
     * @param class-string<Serializer> $serializerClassName
     * @return void
     * @throws SerializerException
     */
    public function registerSerializer(string $serializerClassName): void
    {
        $targetClass = $serializerClassName::$serializesClass;

        if ($targetClass === '') {
            throw new InvalidTargetClassException($serializerClassName);
        }

        if (is_string($variant = $serializerClassName::$variant)) {
            if (!array_key_exists($targetClass, $this->serializers)) {
                $this->serializers[$targetClass] = [];
            } else if (!is_array($this->serializers[$targetClass])) {
                throw new MixedUpVariantException($serializerClassName);
            }

            $this->serializers[$targetClass][$variant] = $serializerClassName;
        } else {
            $this->serializers[$targetClass] = $serializerClassName;
        }
    }

    /**
     * @param object $object
     * @param array $structure
     * @return array
     * @throws SerializerException
     */
    private function serializeObjectWithStructure(object $object, array $structure): array
    {
        $result = [];

        foreach ($structure as $structureKey => $structureValue) {
            /** @var string $propertyName */
            /** @var FieldBehavior[] $behaviors */
            $behaviors = [];

            if (is_string($structureKey)) {
                $propertyName = $structureKey;

                if (is_callable($structureValue)) {
                    $structureValue = new GeneratedValueBehavior($structureValue);
                }

                if ($structureValue instanceof FieldBehavior) {
                    $behaviors = [$structureValue];
                } else if ($structureValue instanceof MultipleBehaviors) {
                    $behaviors = $structureValue->behaviors;
                } else {
                    throw new UnrecognizedFieldConfigurationException($structureKey, $structureValue);
                }
            } else if (is_int($structureKey) && is_string($structureValue)) {
                $propertyName = $structureValue;
            } else {
                throw new UnrecognizedFieldConfigurationException($structureKey, $structureValue);
            }

            $propertyExists = property_exists($object, $propertyName);
            $value = $propertyExists ? $object->$propertyName : null;
            $serializedPropertyName = $propertyName;

            foreach ($behaviors as $behavior) {
                if ($behavior instanceof GeneratedValueBehavior) {
                    if ($propertyExists) {
                        throw new InvalidBehaviorException(
                            get_class($behavior), get_class($object), $propertyName,
                            'Generating values is only suitable for properties that do not exist in the 
                            original object.'
                        );
                    }

                    $value = call_user_func($behavior->generator, $object);
                }

                if (!$propertyExists && $value === null) {
                    throw new InvalidBehaviorException(
                        get_class($behavior), get_class($object), $propertyName,
                        'The property does not exist on the object currently being serialized. To add 
                        properties to the serialized object that do not exist in the original object, use the 
                        GeneratedValueBehavior, or assign a proper callable to the property in the structure 
                        definition.'
                    );
                }

                if ($behavior instanceof ChangeNameBehavior) {
                    $serializedPropertyName = $behavior->newName;
                } else if ($behavior instanceof ModifyValueBehavior) {
                    $value = call_user_func($behavior->modifier, $value);
                } else if ($behavior instanceof SerializeBehavior) {
                    $value = $this->serializeIfPossible(
                        $object->$propertyName, $behavior->variant, $behavior->primitive
                    );
                } else if ($behavior instanceof ConvertDateBehavior) {
                    if (!($value instanceof DateTimeInterface)) {
                        $valueType = get_class($value);

                        throw new InvalidBehaviorException(
                            get_class($behavior), get_class($object), $propertyName,
                            "Tried passing object of type $valueType to a DateTime converter."
                        );
                    }

                    if ($this->dateTimeConverter === null) {
                        throw new ConverterNotDefined(
                            get_class($behavior), get_class($object), $propertyName, ISerializerDateTimeConverter::class
                        );
                    }

                    $value = $this->dateTimeConverter->convert($value, $behavior->format);
                }
            }

            $result[$serializedPropertyName] = $value;
        }

        return $result;
    }

    /**
     * @param float|object|bool|int|string|array|null $object
     * @param string|null $variant
     * @param bool $usePrimitiveSerializer
     * @return float|object|bool|int|string|array|null
     * @throws SerializerException
     */
    private function serializeIfPossible(
        float|object|bool|int|string|array|null $object,
        ?string $variant = null,
        bool $usePrimitiveSerializer = false
    ): float|object|bool|int|string|array|null
    {
        if ($object === null) return null;
        if (is_scalar($object)) return $object;

        if (is_array($object)) {
            $result = [];

            foreach ($object as $key => $value) {
                $result[$key] = $this->serializeIfPossible($value, $variant, $usePrimitiveSerializer);
            }

            return $result;
        } else if ($object instanceof Collection) {
            $result = [];

            foreach ($object as $value) {
                $result[] = $this->serializeIfPossible($value, $variant, $usePrimitiveSerializer);
            }

            return $result;
        }

        $objectClass = get_class($object);
        // Workaround for Doctrine proxy classes
        $objectClass = str_replace('DoctrineProxies\\__CG__\\', '', $objectClass);

        if (array_key_exists($objectClass, $this->serializers)) {
            /** @var class-string<Serializer>[]|class-string<Serializer> $availableSerializers */
            $availableSerializers = $this->serializers[$objectClass];

            /** @var Serializer $serializer */

            if (is_array($availableSerializers)) {
                if ($variant === null) {
                    throw new VariantNotSpecifiedException($objectClass);
                }

                $serializer = new $availableSerializers[$variant];
            } else {
                $serializer = new $availableSerializers;
            }

            if (!$usePrimitiveSerializer) {
                $structure = $serializer->getSerializationStructure();

                if (is_array($structure)) {
                    $serialized = $this->serializeObjectWithStructure($object, $structure);
                    $typeName = (new ReflectionObject($object))->getShortName();
                    $serialized['@type'] = $variant === null ? $typeName : "$typeName-$variant";

                    return $serialized;
                }
            }

            $asPrimitive = $serializer->serializePrimitive($object);

            if ($asPrimitive === null) {
                throw new SerializationNotImplementedException();
            }

            return $asPrimitive;
        } else {
            return $object;
        }
    }

    /**
     * @param array|object|null $object
     * @param string|null $variant
     * @param bool $primitive
     * @param callable $encoder gets passed the serialized data as an array (or <b>null</b> if <b>$object</b> is
     * <b>null</b>) and should return the data converted to a string representation of choice.
     * @return string
     * @throws SerializerException
     */
    public function encode(array|object|null $object, ?string $variant, bool $primitive, callable $encoder): string
    {
        return $encoder($this->serializeIfPossible($object, $variant, $primitive));
    }
}