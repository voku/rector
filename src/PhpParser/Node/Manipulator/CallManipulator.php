<?php declare(strict_types=1);

namespace Rector\PhpParser\Node\Manipulator;

use Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use Rector\NodeContainer\ParsedNodesByType;
use Rector\PhpParser\Node\BetterNodeFinder;
use Rector\PhpParser\Node\Resolver\NameResolver;
use Rector\PhpParser\Parser\Parser;
use Rector\PhpParser\Printer\BetterStandardPrinter;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Functions, static and method calls
 */
final class CallManipulator
{
    /**
     * @var string[]
     */
    private const VARIADIC_FUNCTION_NAMES = ['func_get_args', 'func_get_arg', 'func_num_args'];

    /**
     * @var ParsedNodesByType
     */
    private $parsedNodesByType;

    /**
     * @var BetterNodeFinder
     */
    private $betterNodeFinder;

    /**
     * @var NameResolver
     */
    private $nameResolver;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    public function __construct(
        ParsedNodesByType $parsedNodesByType,
        BetterNodeFinder $betterNodeFinder,
        NameResolver $nameResolver,
        Parser $parser,
        BetterStandardPrinter $betterStandardPrinter
    ) {
        $this->parsedNodesByType = $parsedNodesByType;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->nameResolver = $nameResolver;
        $this->parser = $parser;
        $this->betterStandardPrinter = $betterStandardPrinter;
    }

    /**
     * @param ReflectionMethod|ReflectionFunction $reflectionFunctionAbstract
     * @param StaticCall|FuncCall|MethodCall $callNode
     */
    public function isVariadic(ReflectionFunctionAbstract $reflectionFunctionAbstract, Node $callNode): bool
    {
        // detects from ... in parameters
        if ($reflectionFunctionAbstract->isVariadic()) {
            return true;
        }

        if ($this->isVariadicByName($reflectionFunctionAbstract)) {
            return true;
        }

        if ($reflectionFunctionAbstract instanceof ReflectionFunction) {
            $functionNode = $this->parsedNodesByType->findFunction($reflectionFunctionAbstract->getName());
            if ($functionNode === null) {
                return false;
            }

            return $this->containsFuncGetArgsFuncCall($functionNode);
        }

        $classMethodNode = $this->parsedNodesByType->findMethod(
            $reflectionFunctionAbstract->getName(),
            $reflectionFunctionAbstract->getDeclaringClass()->getName()
        );

        if ($classMethodNode !== null) {
            return $this->containsFuncGetArgsFuncCall($classMethodNode);
        }
        return $this->isExternalScopeVariadic($reflectionFunctionAbstract, $callNode);
    }

    private function containsFuncGetArgsFuncCall(Node $node): bool
    {
        return (bool) $this->betterNodeFinder->findFirst($node, function (Node $node): ?bool {
            if (! $node instanceof FuncCall) {
                return null;
            }

            return $this->nameResolver->isNames($node, self::VARIADIC_FUNCTION_NAMES);
        });
    }

    /**
     * @param StaticCall|FuncCall|MethodCall $callNode
     */
    private function isExternalScopeVariadic(
        ReflectionFunctionAbstract $reflectionFunctionAbstract,
        Node $callNode
    ): bool {
        if (! $reflectionFunctionAbstract->getFileName()) {
            return false;
        }

        /** @var string $filePath */
        $filePath = $reflectionFunctionAbstract->getFileName();
        if (! file_exists($filePath)) {
            return true;
        }

        $externalFileContent = $this->parser->parseFile($filePath);
        $requiredExternalType = $this->resolveMotherType($callNode);
        $functionName = $reflectionFunctionAbstract->getName();

        /** @var Function_|ClassMethod|null $externalFunctionNode */
        $externalFunctionNode = $this->betterNodeFinder->findFirst($externalFileContent, function (Node $node) use (
            $requiredExternalType,
            $functionName
        ): ?bool {
            if (! is_a($node, $requiredExternalType, true)) {
                return null;
            }

            if (! $this->nameResolver->isName($node, $functionName)) {
                return null;
            }

            return true;
        });

        // we didn't find it, nothing we can do
        if ($externalFunctionNode === null) {
            return false;
        }

        $printedFunction = $this->betterStandardPrinter->print($externalFunctionNode->stmts);

        return (bool) Strings::match($printedFunction, '#\b(' . implode('|', self::VARIADIC_FUNCTION_NAMES) . ')\b#');
    }

    private function resolveMotherType(Node $callNode): string
    {
        return $callNode instanceof FuncCall ? Function_::class : ClassMethod::class;
    }

    /**
     * native PHP bug fix
     */
    private function isVariadicByName(ReflectionFunctionAbstract $reflectionFunctionAbstract): bool
    {
        if (! $reflectionFunctionAbstract instanceof ReflectionMethod) {
            return false;
        }

        if ($reflectionFunctionAbstract->getDeclaringClass()->getName() !== 'ReflectionMethod') {
            return false;
        }

        return $reflectionFunctionAbstract->getName() === 'invoke';
    }
}
