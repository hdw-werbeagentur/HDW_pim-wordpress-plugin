<?php

namespace HDW\ProjectDmsImporter\Contracts;

interface ProductContract
{
    public function getDescription(): string;
    public function getShortDescription(): string;
    public function getId(): string;
    public function getStatus(): string;
    public function getProductType(): string;
    public function getVariants(): array; 
    public function getImage(): string;
    public function getThumbnail(): string; 
    public function getThumbnails(): array; 
    public function getName(): string;
    public function getSku(): string;
    public function getBrand(): string;
    public function getMasterNumber(): string;
    public function getFormat();
    public function getOrderQuantity(): string;
    public function getSalesUnits(): string;
    public function getIndustries(): array;
    public function getPackagingType(): string;
    public function getPropertiesUsp(): string;
    public function getIconsUsp(): array;
    public function getProfile(): string;
    public function getEcoFlowerNr(): string;
    public function getNordicSwanNr(): string;
    public function getSds(): string;
    public function getSiTi(): string;
    public function getOperatingInstructionsDe(): string;
    public function getApplicationPictogramsPicture(): array;
    public function getApplicationPictogramsText(): string;
    public function getApplicationCategory(): array;
    public function getApplicationRangeSiTi(): string;
    public function getScopeOfApplicationPicture(): string;
    public function getApplicationPurposes(): array;
    public function getDosage(): string;
    public function getProductComposition(): string;
    public function getSurfaceMaterial(): array;
    public function getPhValue(): string;
    public function getColourOdour(): array;
    public function getWaterHardness(): array;
    public function getDosingSystems(): string;
    public function getEanCode(): string; 
    public function getDosageTable(): string; 
    public function getDisinfectionTable(): string; 
    public function getProductCertificates(): array; 
    public function getCLPLabelling(): string; 
    public function getMainFeatures(): string; 
}
