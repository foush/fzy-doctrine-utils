<?php
namespace FzyDoctrineUtils\Service;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use FzyUtils\Page;
use FzyUtils\Params;
use FzyUtils\Result;

class Search implements SearchInterface {

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repo;

    /**
     * @param EntityRepository|null $repo
     */
    public function __construct(EntityRepository $repo = null)
    {
        if ($repo !== null) {
            $this->setRepository($repo);
        }
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * @param \Doctrine\ORM\EntityRepository $repo
     * @return $this
     */
    public function setRepository(EntityRepository $repo)
    {
        $this->repo = $repo;
        return $this;
    }



    /**
     * @param Page $page
     * @return \FzyUtils\Result
     */
    public function getList(Page $page, Params $params)
    {
        $total = $this->getCount($params);
        return new Result($total < 1 ? [] : $this->getResults($page, $params), $page, $total);
    }

    /**
     * Applies search query via $params and invokes
     * $callable on each result. $chunkSize specifies
     * how many entities will be read into memory at once.
     *
     * @param Params $params
     * @param $callable
     * @param int $chunkSize
     * @return $this
     * @throws \FzyUtils\Exception\Configuration\Invalid
     */
    public function traverseResults(Params $params, $callable, $chunkSize = 100)
    {
        $total = $this->getCount($params);
        $page = new Page(0, $chunkSize);
        $page->setLimitBounds($chunkSize, $chunkSize);
        while ($page->getOffset() < $total) {
            $set = $this->getResults($page, $params);
            $i = 0;
            foreach ($set as $item) {
                call_user_func($callable, $item, $page->getOffset() + $i++);
            }
            $page->setOffset($page->getOffset() + $page->getLimit());
        }
        return $this;
    }

    /**
     * @param Params $params
     * @return int
     */
    protected function getCount(Params $params)
    {
        return $this->getCountQuery($params)->getSingleScalarResult();
    }

    /**
     * @param Params $params
     * @return \Doctrine\ORM\Query
     */
    protected function getCountQuery(Params $params)
    {
        $alias = $this->getAlias();
        $qb = $this->repo->createQueryBuilder($alias)
            ->select("COUNT({$alias}.id)");
        $this->applyListFilters($qb, $params);
        return $qb->getQuery();
    }

    /**
     * Adds WHERE clauses and joins as needed for any search query done on this repo
     * @param QueryBuilder $qb
     * @param Params $params
     */
    protected function applyListFilters(QueryBuilder $qb, Params $params)
    {
        $this->applyRepoFilters($qb, $params);
    }

    /**
     * Adds WHERE clauses and joins as needed for any (id or search) query done on this repo
     * @param QueryBuilder $qb
     * @param Params $params
     */
    protected function applyRepoFilters(QueryBuilder $qb, Params $params)
    {
        // noop
    }

    protected function getResults(Page $page, Params $params)
    {
        return $this->getResultsQuery($page, $params)->getResult();
    }

    protected function getResultsQuery(Page $page, Params $params)
    {
        $alias = $this->getAlias();
        $qb = $this->repo->createQueryBuilder($alias);
        $this->applyListFilters($qb, $params);
        $this->applyListPagination($qb, $page, $params);
        $this->applyListOrdering($qb, $page, $params);
        return $qb->getQuery();
    }

    /**
     * Applies pagination offset/limit values to the QueryBuilder
     * @param QueryBuilder $qb
     * @param Page $page
     * @param Params $params
     */
    protected function applyListPagination(QueryBuilder $qb, Page $page, Params $params)
    {
        $qb->setFirstResult($page->getOffset())
            ->setMaxResults($page->getLimit());
    }

    /**
     * Applies ordering criteria to a query builder.
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param \FzyUtils\Page $page
     * @param \FzyUtils\Params $params
     */
    protected function applyListOrdering(QueryBuilder $qb, Page $page, Params $params)
    {
        // noop
    }

    /**
     * @param $id
     * @return Result
     */
    public function getIndividual($id, Params $params = null)
    {
        if ($params === null) {
            $params = new Params();
        }
        $qb = $this->repo->createQueryBuilder($this->getAlias());
        $this->applyIndividualFilters($qb, $id, $params);
        $this->applyRepoFilters($qb, $params);
        $entity = null;
        try {
            $entity = $qb->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // no entity matched criteria
        }

        return new Result($entity ? [$entity] : [], new Page(0, 1));
    }

    protected function applyIndividualFilters(QueryBuilder $qb, $id, Params $params)
    {
        $qb->andWhere($this->alias('id', '= :id'))->setParameter('id', $id);
    }

    /**
     * Query alias used to refer to this entity
     * @return string
     */
    public function getAlias()
    {
        return 'e';
    }

    /**
     * Convenience method
     * Returns $prefix.$property $suffix
     * If $prefix is null, it is set to $this->getAlias()
     *
     * @param $property
     * @param string $suffix
     * @param null $prefix
     * @return string
     */
    protected function alias($property, $suffix = '', $prefix = null)
    {
        return ($prefix !== null ? $prefix : $this->getAlias()) . '.' . $property . ' ' .$suffix;
    }
}