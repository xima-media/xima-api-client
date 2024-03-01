<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Pagination;

use TYPO3\CMS\Core\Pagination\AbstractPaginator;

final class RequestPaginator extends AbstractPaginator
{
    protected array $items = [];
    protected array $paginatedItems = [];

    public function __construct(
        protected int $totalAmountOfItems,
        int $currentPageNumber = 1,
        int $itemsPerPage = 10
    ) {
        // create fake total items out of paginated items
        $items = [];

        for ($i = 0; $i < $this->totalAmountOfItems; $i++) {
            $items[] = $i;
        }

        $this->items = $items;

        $this->setCurrentPageNumber($currentPageNumber);
        $this->setItemsPerPage($itemsPerPage);

        $this->updateInternalState();
    }

    public function getPaginatedItems(): array
    {
        return $this->paginatedItems;
    }

    protected function updatePaginatedItems(int $itemsPerPage, int $offset): void
    {
        $this->paginatedItems = array_slice($this->items, $offset, $itemsPerPage);
    }

    protected function getTotalAmountOfItems(): int
    {
        return $this->totalAmountOfItems;
    }

    protected function getAmountOfItemsOnCurrentPage(): int
    {
        return count($this->paginatedItems);
    }
}
