<?php

namespace React\Dns\Resolver;

interface ResolverInterface
{
    
    public function resolve($domain);

    
    public function resolveAll($domain, $type);
}
