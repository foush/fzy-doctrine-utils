<?php
namespace FzyDoctrineUtils\Service;

use FzyUtils\Page;
use FzyUtils\Params;
use FzyUtils\Result;


interface SearchInterface extends RepositoryAwareInterface {
    /**
     * @param Page $page
     * @return \FzyUtils\Result
     */
    public function getList(Page $page, Params $params);

    /**
     * @param $id
     * @return Result
     */
    public function getIndividual($id, Params $params = null);

    /**
     * @return mixed
     */
    public function getAlias();
}