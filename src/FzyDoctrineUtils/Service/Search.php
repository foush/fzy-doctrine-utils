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
        $qb->andWhere($this->getAlias().'.id = :id')->setParameter('id', $id);
    }

    public function getAlias()
    {
        return 'e';
    }
}