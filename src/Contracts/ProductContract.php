<?php

namespace HDW\ProjectDmsImporter\Contracts;

interface ProductContract
{
    public function getDescription(): string;
    public function getShortDescription(): string;
    public function getId(): string;
    public function getStatus(): string;
    public function getImage(): string;
    public function getThumbnail(): string; 
    public function getName(): string;
    public function getSku(): string;
    public function getBrand(): string;
    public function getMasterNumber(): string;
    public function getFormat(): string;
    public function getSalesUnits(): string;
    public function getPackagingType(): string;
    public function getPropertiesUsp(): string;
    public function getIconsUsp(): string;
    public function getProfile(): string;
    public function getEcoFlowerNr(): string;
    public function getNordicSwanNr(): string;
    public function getSds(): string;
    public function getSiTi(): string;
    public function getOperatingInstructionsDe(): string;
    public function getApplicationPictogramsPicture(): string;
    public function getApplicationPictogramsText(): string;
    public function getApplicationCategory(): string;
    public function getApplicationRangeSiTi(): string;
    public function getScopeOfApplicationPicture(): string;
    public function getApplicationPurposes(): string;
    public function getDosage(): string;
    public function getProductComposition(): string;
    public function getSurfaceMaterial(): string;
    public function getPhValue(): string;
    public function getColourOdour(): string;
    public function getWaterHardness(): string;
    public function getDosingSystems(): string;
    public function getEanCode(): string; 
    public function getDosageTable(): string; 
    public function getDisinfectionTable(): string; 
    public function getProductCertificates(): string; 
    // public function getArticle(): string;
    // public function getAttribute(string $key): array;
    // public function getAttributeGroup(string $key): array;
    // public function getCategories(): array;
    // public function getWeight(): string;
    // public function hasVariations(): bool;
    // public function getImages(): array;
    // public function getSizes(): array;
    // public function getPostThumbnailId(): int;
    // public function getPrices(): array;
    // public function getPrice(string $size): int;
}
