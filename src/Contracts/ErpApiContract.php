<?php

namespace HDW\ProjectDmsImporter\Contracts;

use HDW\ProjectDmsImporter\Contracts\ProductContract;

interface ErpApiContract
{
    public function getProduct(string $id): ?\stdClass;
    public function getProducts(): array;
    public function getProductStock(string $id): array;
}
