<?php
/**
 * Created by PhpStorm.
 * User: karolkrupa
 * Date: 06/11/2018
 * Time: 12:40
 */

namespace karolkrupa;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Paginator implements PaginatorInterface
{
    private $qb;
    private $itemsPerPage = 10;
    private $page = 1;
    private $result = null;
    private $totalItems = null;
    private $orderBy = null;
    private $orderType = 'ASC';
    private $distinctAlias;
    private $customData = false;

    const EMPTY_RESPONSE = [
        'page' => 1,
        'total_items' => 0,
        'items_per_page' => 0,
        'pages_count' => 0,
        'data' => []
    ];

    public function __construct(QueryBuilder $qb, $distinctAlias)
    {
        $this->qb = $qb;
        $this->distinctAlias = $distinctAlias;
        $this->result = new ArrayCollection();
    }

    public function handleRequest(Request $request): self
    {
        $this->setItemsPerPage($request->get('items_per_page', 10));
        $this->setPage($request->get('page', 1));
        $this->setOrderBy($request->get('order_by'));
        $this->setOrderType($request->get('order_type'));

        return $this;
    }

    public function createResponse($customData = null): array
    {
        if($customData !== null) {
            $this->setCustomData($customData);
        }

        $response = [
            'page' => $this->getPage(),
            'total_items' => intval($this->getTotalCount()),
            'items_per_page' => $this->getItemsPerPage(),
            'pages_count' => $this->getPagesCount(),
            'data' => $this->getItems()
        ];

        return $response;
    }

    public function getTotalCount()
    {
        if ($this->totalItems) {
            return $this->totalItems;
        }
        $qb = clone $this->qb;
        $qb->select("COUNT(DISTINCT $this->distinctAlias) as count");
        $this->totalItems = $qb->getQuery()->getScalarResult()[0]['count'];
        return $this->totalItems;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(int $count): self
    {
        $this->totalItems = null;
        $this->result->clear();
        $this->itemsPerPage = $count;

        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->totalItems = null;
        $this->result->clear();
        if ($page > $this->getPagesCount()) {
            $this->page = $this->getPagesCount();
        } else {
            $this->page = $page;
        }

        return $this;
    }

    public function setOrderBy(string $orderBy = null)
    {
        $this->totalItems = null;
        $this->result->clear();
        if ($orderBy !== null) {
            $this->orderBy = $orderBy;
        }
    }

    /**
     * @return null
     */
    public function getOrderBy(): ?string
    {
        return $this->orderBy ? Inflector::camelize($this->orderBy) : $this->orderBy;
    }

    public function setOrderType(string $orderType = null)
    {
        $this->totalItems = null;
        $this->result->clear();
        if ($orderType !== null) {
            $this->orderType = $orderType;
        }
    }

    /**
     * @return null
     */
    public function getOrderType(): string
    {
        return $this->orderType;
    }

    public function execute()
    {
        $this->customData = false;
        $qb = clone $this->qb;
        $qb->setFirstResult($this->getFirstResultNumber());
        if ($this->getOrderBy() !== null) {
            if (strpos($this->getOrderBy(), '.') !== false) {
                $qb->join($this->distinctAlias . '.' . $this->getJoinEntityFromOrderBy(), 'joinEntityAlias');
                $qb->addOrderBy('joinEntityAlias.' . $this->getJoinEntityFieldFromOrderBy(), $this->getOrderType());
            } else {
                $qb->addOrderBy($this->distinctAlias . '.' . $this->getOrderBy(), $this->getOrderType());
            }
        }
        if ($this->itemsPerPage > 0) {
            $qb->setMaxResults($this->itemsPerPage);
        }
        $items = new \Doctrine\ORM\Tools\Pagination\Paginator($qb, true);
        foreach ($items as $item) {
            $this->result->add($item);
        }
        return $this;
    }

    public function getItems(): Collection
    {
        if ($this->result->count() < 1) {
            $this->execute();
        }
        return $this->result;
    }

    public function getPagesCount(): int
    {
        return ceil($this->getTotalCount() / $this->itemsPerPage);
    }

    private function setCustomData(ArrayCollection $customData)
    {
        $this->customData = true;
        $this->result = $customData;
        //$this->totalItems = $customData->count();
    }

    private function getFirstResultNumber()
    {
        if ($this->page <= 1) {
            return 0;
        } else {
            return ($this->page - 1) * $this->itemsPerPage;
        }
    }

    private function getJoinEntityFromOrderBy()
    {
        return explode('.', $this->getOrderBy())[0];
    }

    private function getJoinEntityFieldFromOrderBy()
    {
        return explode('.', $this->getOrderBy())[1];
    }
}
