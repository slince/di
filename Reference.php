<?php

declare(strict_types=1);

/*
 * This file is part of the slince/di package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Slince\Di;

final class Reference
{
    /**
     * Service ID
     * @var string
     */
    protected string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Get service ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}