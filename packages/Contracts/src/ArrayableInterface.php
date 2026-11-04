<?php
/**
 * Object can be converted to array
 *
 * @package SharkyPHP\\Contracts
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 */
declare (strict_types = 1);

namespace Sharky\Contracts;

interface ArrayableInterface
{
    /**
     * Get array representation of object
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
