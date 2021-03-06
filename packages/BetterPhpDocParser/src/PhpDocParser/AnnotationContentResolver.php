<?php declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocParser;

use Nette\Utils\Strings;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use Rector\BetterPhpDocParser\PhpDocInfo\TokenIteratorFactory;

final class AnnotationContentResolver
{
    /**
     * @var TokenIteratorFactory
     */
    private $tokenIteratorFactory;

    public function __construct(TokenIteratorFactory $tokenIteratorFactory)
    {
        $this->tokenIteratorFactory = $tokenIteratorFactory;
    }

    /**
     * Skip all tokens for this annotation, so next annotation can work with tokens after this one
     * Inspired at @see \PHPStan\PhpDocParser\Parser\PhpDocParser::parseText()
     */
    public function resolveFromTokenIterator(TokenIterator $tokenIterator): string
    {
        $annotationContent = '';
        $unclosedOpenedBracketCount = 0;

        while (true) {
            if ($tokenIterator->currentTokenType() === Lexer::TOKEN_OPEN_PARENTHESES) {
                ++$unclosedOpenedBracketCount;
            }

            if ($tokenIterator->currentTokenType() === Lexer::TOKEN_CLOSE_PARENTHESES) {
                --$unclosedOpenedBracketCount;
            }

            if ($unclosedOpenedBracketCount === 0 && $tokenIterator->currentTokenType() === Lexer::TOKEN_PHPDOC_EOL) {
                break;
            }

            // remove new line "*"
            if (Strings::contains($tokenIterator->currentTokenValue(), '*')) {
                $tokenValueWithoutAsterisk = Strings::replace($tokenIterator->currentTokenValue(), '#\*#');
                $annotationContent .= $tokenValueWithoutAsterisk;
            } else {
                $annotationContent .= $tokenIterator->currentTokenValue();
            }

            $tokenIterator->next();
        }

        return $this->cleanMultilineAnnotationContent($annotationContent);
    }

    public function resolveNestedKey(string $annotationContent, string $name): string
    {
        try {
            $start = false;
            $openedCurlyBracketCount = 0;
            $tokenContents = [];

            $tokenIterator = $this->tokenIteratorFactory->create($annotationContent);

            while (true) {
                // the end
                if (in_array($tokenIterator->currentTokenType(), [Lexer::TOKEN_CLOSE_PHPDOC, Lexer::TOKEN_END], true)) {
                    break;
                }

                $start = $this->tryStartWithKey($name, $start, $tokenIterator);
                if ($start === false) {
                    $tokenIterator->next();
                    continue;
                }

                $tokenContents[] = $tokenIterator->currentTokenValue();

                // opening bracket {
                if ($tokenIterator->isCurrentTokenType(Lexer::TOKEN_OPEN_CURLY_BRACKET)) {
                    ++$openedCurlyBracketCount;
                }

                // closing bracket }
                if ($tokenIterator->isCurrentTokenType(Lexer::TOKEN_CLOSE_CURLY_BRACKET)) {
                    --$openedCurlyBracketCount;

                    // the final one
                    if ($openedCurlyBracketCount === 0) {
                        break;
                    }
                }

                $tokenIterator->next();
            }

            return implode('', $tokenContents);
        } catch (ParserException $parserException) {
            throw $parserException;
        }
    }

    private function cleanMultilineAnnotationContent(string $annotationContent): string
    {
        return Strings::replace($annotationContent, '#(\s+)\*(\s+)#m', '$1$3');
    }

    private function tryStartWithKey(string $name, bool $start, TokenIterator $localTokenIterator): bool
    {
        if ($start === true) {
            return true;
        }

        if ($localTokenIterator->isCurrentTokenType(Lexer::TOKEN_IDENTIFIER)) {
            if ($localTokenIterator->currentTokenValue() === $name) {
                // consume "=" as well
                $localTokenIterator->next();
                $localTokenIterator->tryConsumeTokenType(Lexer::TOKEN_EQUAL);

                return true;
            }
        }

        return false;
    }
}
