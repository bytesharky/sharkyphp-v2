<?php
/**
 * Object can be converted to json string
 *
 * @package SharkyPHP\\Contracts
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 */
declare (strict_types = 1);

namespace Sharky\Contracts;

interface JsonableInterface
{
    /**
     * Get the json representation of the object.
     *
     * @param int $options json_encode options
     * @return string
     */
    public function toJson(int $options = 0): string;
}
