<?php

namespace FpDbTest;

interface DatabaseInterface
{
    public function buildQuery(string $query, array $args = []);

    public function skip();
}
