<?php declare(strict_types=1);

namespace PhpParser\Node\Expr\Cast;

use PhpParser\Node\Expr\Cast;

class Bool_ extends Cast {
    
    public const KIND_BOOL = 1; 
    public const KIND_BOOLEAN = 2; 

    public function getType(): string {
        return 'Expr_Cast_Bool';
    }
}
