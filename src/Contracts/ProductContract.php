<?php

namespace HDW\ProjectDmsImporter\Contracts;

interface ProductContract
{
    public function getArticle(): string;
    public function getAttribute(string $key): array;
    public function getAttributeGroup(string $key): array;
    public function getCategories(): array;
    public function getColors(): array;
    public function getColorNames(): array;
    public function getDescription(): string;
    public function getId(): string;
    public function getImages(): array;
    public function getName(): string;
    public function getPostThumbnailId(): int;
    public function getPrices(): array;
    public function getPrice(string $size): int;
    public function getSizes(): array;
    public function getStatus(): string;
    public function getSku(): string;
    public function getWeight(): string;
    public function hasVariations(): bool;
}
