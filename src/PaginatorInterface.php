<?php

namespace karolkrupa;


use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;

interface PaginatorInterface {
    const DESC = 'DESC';
    const ASC = 'ASC';

    public function getPage() : int;
    public function setPage(int $page);

    public function setItemsPerPage(int $itemsPerPage);
    public function getItemsPerPage() : int;

    public function setOrderBy(string $orderBy = null);
    public function getOrderBy() : string;

    public function setOrderType(string $type);
    public function getOrderType() : ?string;

    public function getPagesCount() : int;

    public function execute();

    public function handleRequest(Request $request);
    public function createResponse($customData = null) : array;

    public function getItems() : Collection;
}
