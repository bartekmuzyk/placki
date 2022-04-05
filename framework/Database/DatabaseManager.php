<?php

namespace Framework\Database;

use App\Entities\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\TransactionRequiredException;

class DatabaseManager {
	private EntityManager $entityManager;

	/**
	 * @throws ORMException
	 */
	public function __construct(string $host, string $dbname, string $username, string $password)
	{
		$config = Setup::createAnnotationMetadataConfiguration(
			[PROJECT_ROOT . '/src/Entities'],
			false,
			null,
			null,
			false
		);
		$this->entityManager = EntityManager::create([
			'driver' => 'pdo_mysql',
			'host' => $host,
			'user' => $username,
			'password' => $password,
			'dbname' => $dbname
		], $config);
	}

	/**
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
	public function persist(object $entity)
	{
		$this->entityManager->persist($entity);
	}

	/**
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function flush()
	{
		$this->entityManager->flush();
	}

	/**
	 * @param object $entity
	 * @return void
	 * @throws ORMException
	 * @throws OptimisticLockException
	 */
	public function persistAndFlush(object $entity)
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
	public function find(string $entityClassName, $id): ?object
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
	 * @return object[]
	 */
	public function getAll(string $entityClassName): array
	{
		return $this->query('e', $entityClassName)->getQuery()->getResult();
	}
}