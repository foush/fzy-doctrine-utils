<?php
namespace FzyDoctrineUtils\Service;

use Doctrine\ORM\EntityRepository;

interface RepositoryAwareInterface {
    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository();

    /**
     * @param \Doctrine\ORM\EntityRepository $repo
     */
    public function setRepository(EntityRepository $repo);


}