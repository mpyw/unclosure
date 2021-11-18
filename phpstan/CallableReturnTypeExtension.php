<?php

namespace Mpyw\Unclosure\PHPStan;

use Mpyw\Unclosure\Value;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

final class CallableReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Value::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return \in_array(
            $methodReflection->getName(),
            ['withEffect', 'withEffectForEach', 'withCallback'],
            true,
        );
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): \PHPStan\Type\Type
    {
        if ($methodReflection->getName() === 'withCallback') {
            return self::withCallbackType($methodCall, $scope);
        }

        if (\in_array($methodReflection->getName(), ['withEffect', 'withEffectForEach'], true)) {
            return self::withEffectType($methodCall, $scope);
        }

        return new MixedType();
    }

    private static function withCallbackType(StaticCall $methodCall, Scope $scope): Type
    {
        if (\count($methodCall->getArgs()) > 1) {
            $valueType = $scope->getType($methodCall->getArgs()[0]->value);
            $callbackType = $scope->getType($methodCall->getArgs()[1]->value);

            if ($callbackType instanceof ParametersAcceptor) {
                return $valueType instanceof ClosureType
                    ? new ClosureType(
                        [new CallableArgumentParameter()],
                        $callbackType->getReturnType(),
                        false,
                    )
                    : $callbackType->getReturnType();
            }
        }

        return new MixedType();
    }

    private static function withEffectType(StaticCall $methodCall, Scope $scope): Type
    {
        if (\count($methodCall->getArgs()) > 2) {
            $callbackType = $scope->getType($methodCall->getArgs()[2]->value);

            if ($callbackType instanceof ParametersAcceptor) {
                return $callbackType->getReturnType();
            }
        }

        return new MixedType();
    }
}
