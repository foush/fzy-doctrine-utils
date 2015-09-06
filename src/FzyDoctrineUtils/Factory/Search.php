<?php
namespace FzyDoctrineUtils\Factory;

use Doctrine\ORM\EntityManager;
use FzyDoctrineUtils\Exception\InvalidServiceClass as InvalidServiceClassException;
use FzyDoctrineUtils\Service\RepositoryAwareInterface;
use FzyDoctrineUtils\Service\Search as SearchService;
use FzyDoctrineUtils\Service\SearchInterface;

class Search {

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param $repoName
     * @param null $getterService
     * @return SearchService
     */
    public function forRepository($repoName, $getterService = null)
    {
        if ($getterService === null) {
            $getterService = '\FzyDoctrineUtils\Service\Search';
        }
        $service = new $getterService($this->em->getRepository($repoName));
        if (!$service instanceof RepositoryAwareInterface) {
            throw new InvalidServiceClassException("'$getterService' does not implement the RepositoryAwareInterface");
        }
        $service->setRepository($this->em->getRepository($repoName));
        return $service;
    }

}