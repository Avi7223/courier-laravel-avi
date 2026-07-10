<?php

namespace Rajibbinalam\BagistoCourier\Exceptions;

use Exception;

class CourierException extends Exception
{
    protected array $context = [];

    public function setContext(array $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
