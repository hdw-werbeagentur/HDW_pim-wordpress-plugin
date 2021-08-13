<?php

namespace HDW\ProjectDmsImporter\Contracts;

interface LanguageContract
{
    public function getId(): string;
    public function getName(): string;
    public function getIso(): string;
}
