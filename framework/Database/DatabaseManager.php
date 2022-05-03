<?php

namespace Framework\Database;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\TransactionRequiredException;
use Ramsey\Uuid\Doctrine\UuidType;

class DatabaseManager {
	private EntityManager $entityManager;

	/**
	 * @throws ORMException
	 * @throws Exception
	 */
	public function __construct(string $host, string $dbname, string $username, string $password)
	{
		$config = ORMSetup::createAnnotationMetadataConfiguration(
			[PROJECT_ROOT . '/src/Entities'],
			false,
			PROJECT_ROOT . '/cache/proxies'
		);
		$this->entityManager = EntityManager::create([
			'driver' => 'pdo_mysql',
			'host' => $host,
			'user' => $username,
			'password' => $password,
			'dbname' => $dbname
		], $config);

		$this->generateProxies();

		Type::addType('uuid', UuidType::class);
	}

	private function generateProxies(): void
    {
		$classes = $this->entityManager->getMetadataFactory()->getAllMetadata();
		$this->entityManager->getProxyFactory()->generateProxyClasses($classes);
	}

	/**
	 * @param array $config
	 * @return DatabaseManager
	 * @throws Exception
	 * @throws ORMException
	 */
	public static function fromConfig(array $config): self
	{
		return new self($config['host'], $config['dbname'], $config['username'], $config['password']);
	}

	public function getEntityManager(): EntityManager
	{
		return $this->entityManager;
	}

	/**
	 * @param object $entity
	 * @return void
	 * @throws ORMException
	 */
	public function persist(object $entity): void
    {
		$this->entityManager->persist($entity);
	}

	/**
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function flush(): void
    {
		$this->entityManager->flush();
	}

	/**
	 * @param object $entity
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function persistAndFlush(object $entity): void
    {
		$this->persist($entity);
		$this->flush();
	}

	/**
	 * @param class-string $entityClassName
	 * @param int|string $id
	 * @return object|null
	 * @throws ORMException
	 * @throws OptimisticLockException
	 * @throws TransactionRequiredException
	 */
	public function find(string $entityClassName, int|string $id): ?object
	{
		return $this->entityManager->find($entityClassName, $id);
	}

	public function bareQuery(): QueryBuilder
	{
		return $this->entityManager->createQueryBuilder();
	}

	/**
	 * @param string $alias
	 * @param class-string $from
	 * @return QueryBuilder
	 */
	public function query(string $alias, string $from): QueryBuilder
	{
		return $this->bareQuery()->select($alias)->from($from, $alias);
	}

	/**
	 * @param class-string $entityClassName
	 * @param int $offset
	 * @param int|null $limit
	 * @param array{order_by: string, direction: string} $order
	 * @return object[]
	 */
	public function getAll(string $entityClassName, int $offset = 0, ?int $limit = null, array $order = []): array
	{
		$queryBuilder = $this->query('e', $entityClassName);

		if ($offset > 0) {
			$queryBuilder->setFirstResult($offset);
		}

		if (is_int($limit)) {
			$queryBuilder->setMaxResults($offset + $limit);
		}

		if (array_key_exists('order_by', $order)) {
			$queryBuilder->orderBy('e.' . $order['order_by'], $order['direction'] ?? 'ASC');
		}

		return $queryBuilder->getQuery()->getResult();
	}

	/**
	 * @param object $entity
	 * @return void
	 * @throws ORMException
	 */
	public function remove(object $entity): void
    {
		$this->entityManager->remove($entity);
	}

	/**
	 * @param object $entity
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function removeAndFlush(object $entity): void
    {
		$this->remove($entity);
		$this->flush();
	}
}