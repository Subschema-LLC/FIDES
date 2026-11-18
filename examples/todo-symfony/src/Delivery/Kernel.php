<?php
namespace App\Delivery;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/** The door Symfony starts from. Symfony requires this base class and
 *  trait, so arch-check.php lists them for this one file, at the edge.
 *  It holds no rules: the container reads config/, and
 *  config/wiring.yaml says which integration fulfills each contract. */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
