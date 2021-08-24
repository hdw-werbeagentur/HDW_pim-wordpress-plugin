<?php

namespace HDW\ProjectDmsImporter\Contracts;

interface ImageContract
{
    public function getName(): string;
    public function getSize(): string;
    public function getWidth(): string;
    public function getHeight(): string;
}
