<?php

declare(strict_types=1);

namespace Wwwision\DCBExample\Tests\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\Type;
use Wwwision\DCBExample\CommandHandler;
use Wwwision\DCBExample\DecisionModel\DecisionModel;
use Wwwision\DCBExample\Projection\Projection;

final readonly class DecisionModelPhpStanExtension implements DynamicMethodReturnTypeExtension
{

    public function getClass(): string
    {
        return CommandHandler::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'buildDecisionModel';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        $args = $methodCall->getArgs();
        $properties = [];
        foreach ($args as $argExpression) {
            $nameOfParam = $argExpression->getAttributes()['originalArg']->name->name;
            /** @var GenericObjectType $projectionObjectType */
            $projectionObjectType = $scope->getType($argExpression->value);
            $properties[$nameOfParam] = $projectionObjectType->getTemplateType(Projection::class, 'S');
        }
        return new GenericObjectType(DecisionModel::class, [new ObjectShapeType($properties, [])]);
    }
}