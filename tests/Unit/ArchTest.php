<?php

declare(strict_types=1);

arch()->expect('App')->classes()->toUseStrictTypes();
arch()->expect('App')->classes()->toUseStrictEquality();

arch()->preset()->php();
arch()->preset()->security();
