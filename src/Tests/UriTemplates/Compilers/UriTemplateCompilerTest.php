<?php

/*
 * Opulence
 *
 * @link      https://www.opulencephp.com
 * @copyright Copyright (C) 2017 David Young
 * @license   https://github.com/opulencephp/route-matcher/blob/master/LICENSE.md
 */

namespace Opulence\Routing\Tests\UriTemplates\Compilers;

use Opulence\Routing\UriTemplates\Compilers\Parsers\AbstractSyntaxTree;
use Opulence\Routing\UriTemplates\Compilers\Parsers\IUriTemplateParser;
use Opulence\Routing\UriTemplates\Compilers\Parsers\Lexers\IUriTemplateLexer;
use Opulence\Routing\UriTemplates\Compilers\Parsers\Lexers\Tokens\TokenStream;
use Opulence\Routing\UriTemplates\Compilers\Parsers\Nodes\Node;
use Opulence\Routing\UriTemplates\Compilers\Parsers\Nodes\NodeTypes;
use Opulence\Routing\UriTemplates\Compilers\UriTemplateCompiler;
use Opulence\Routing\UriTemplates\Rules\IRule;
use Opulence\Routing\UriTemplates\Rules\IRuleFactory;
use Opulence\Routing\UriTemplates\UriTemplate;

/**
 * Tests the URI template compiler
 */
class UriTemplateCompilerTest extends \PHPUnit\Framework\TestCase
{
    /** @var UriTemplateCompiler The compiler to use in tests */
    private $compiler;
    /** @var IUriTemplateParser|\PHPUnit_Framework_MockObject_MockObject The parser to use in tests */
    private $parser;
    /** @var IUriTemplateLexer|\PHPUnit_Framework_MockObject_MockObject The lexer to use in tests */
    private $lexer;
    /** @var IRuleFactory|\PHPUnit_Framework_MockObject_MockObject The rule factory to use in the compiler */
    private $ruleFactory;

    public function setUp(): void
    {
        $this->ruleFactory = $this->createMock(IRuleFactory::class);
        $this->parser = $this->createMock(IUriTemplateParser::class);
        $this->lexer = $this->createMock(IUriTemplateLexer::class);
        // We don't really care about mocking the output of the lexer
        $this->lexer->expects($this->any())->method('lex')->willReturn(new TokenStream([]));
        $this->compiler = new UriTemplateCompiler($this->ruleFactory, $this->parser, $this->lexer);
    }

    public function testCompilingHostAndPathWithSlashesTrimsSlashBetweenThem(): void
    {
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, 'foo.com/bar'));
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate('^foo\.com/bar$', true);
        $actualUriTemplate = $this->compiler->compile('foo.com/', '/bar');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingHttpsOnlyRouteForcesHttpsToBeSet(): void
    {
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo'));
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate('^[^/]+/foo$', false, [], true);
        $actualUriTemplate = $this->compiler->compile(null, '/foo', true);
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingPathWithNoVarsCreatesCorrectRegex(): void
    {
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/bar/baz'));
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate('^[^/]+/foo/bar/baz$', false);
        $actualUriTemplate = $this->compiler->compile(null, '/foo/bar/baz');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingPathWithSingleOptionalVarCreatesCorrectRegex(): void
    {
        $optionalRoutePartNode = new Node(NodeTypes::OPTIONAL_ROUTE_PART, '[');
        $optionalRoutePartNode->addChild(new Node(NodeTypes::TEXT, '/'));
        $optionalRoutePartNode->addChild(new Node(NodeTypes::VARIABLE, 'bar'));
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo'));
        $ast->getCurrentNode()
            ->addChild($optionalRoutePartNode);
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate("^[^/]+/foo(?:/([^\/\.]+))?$", false, ['bar']);
        $actualUriTemplate = $this->compiler->compile(null, '/foo[/:bar]');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingPathWithSingleVarCreatesCorrectRegex(): void
    {
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/'));
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::VARIABLE, 'bar'));
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/baz'));
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate("^[^/]+/foo/([^\/\.]+)/baz$", false, ['bar']);
        $actualUriTemplate = $this->compiler->compile(null, '/foo/:bar/baz');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingPathWithSingleVarWithDefaultValueCreatesCorrectRegex(): void
    {
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/'));
        $variableNode = new Node(NodeTypes::VARIABLE, 'bar');
        $variableNode->addChild(new Node(NodeTypes::VARIABLE_DEFAULT_VALUE, 'blah'));
        $ast->getCurrentNode()
            ->addChild($variableNode);
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/baz'));
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate(
            "^[^/]+/foo/([^\/\.]+)/baz$",
            false,
            ['bar'],
            false,
            ['bar' => 'blah']
        );
        $actualUriTemplate = $this->compiler->compile(null, '/foo/:bar=blah/baz');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingRouteVarWithMultipleRulesCreatesCorrectTemplate(): void
    {
        $rule1 = $this->createMock(IRule::class);
        $rule2 = $this->createMock(IRule::class);
        $this->ruleFactory->expects($this->at(0))
            ->method('createRule')
            ->with('dave')
            ->willReturn($rule1);
        $this->ruleFactory->expects($this->at(1))
            ->method('createRule')
            ->with('alex')
            ->willReturn($rule2);
        $variableNode = new Node(NodeTypes::VARIABLE, 'bar');
        $variableNode->addChild(new Node(NodeTypes::VARIABLE_RULE, 'dave'))
            ->addChild(new Node(NodeTypes::VARIABLE_RULE, 'alex'));
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/'));
        $ast->getCurrentNode()
            ->addChild($variableNode);
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate(
            "^[^/]+/foo/([^\/\.]+)$",
            false,
            ['bar'],
            false,
            [],
            ['bar' => [$rule1, $rule2]]
        );
        $actualUriTemplate = $this->compiler->compile(null, '/foo/:bar(dave,alex)');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingRouteVarWithMultipleRulesThatContainCommaCreatesCorrectTemplate(): void
    {
        $rule1 = $this->createMock(IRule::class);
        $rule2 = $this->createMock(IRule::class);
        $this->ruleFactory->expects($this->at(0))
            ->method('createRule')
            ->with('dave', ['1,2'])
            ->willReturn($rule1);
        $this->ruleFactory->expects($this->at(1))
            ->method('createRule')
            ->with('alex', [3, 4])
            ->willReturn($rule2);
        $daveRuleNode = new Node(NodeTypes::VARIABLE_RULE, 'dave');
        $daveRuleNode->addChild(new Node(NodeTypes::VARIABLE_RULE_PARAMETERS, ['1,2']));
        $alexRuleNode = new Node(NodeTypes::VARIABLE_RULE, 'alex');
        $alexRuleNode->addChild(new Node(NodeTypes::VARIABLE_RULE_PARAMETERS, [3, 4]));
        $variableNode = new Node(NodeTypes::VARIABLE, 'bar');
        $variableNode->addChild($daveRuleNode)
            ->addChild($alexRuleNode);
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/'));
        $ast->getCurrentNode()
            ->addChild($variableNode);
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate(
            "^[^/]+/foo/([^\/\.]+)$",
            false,
            ['bar'],
            false,
            [],
            ['bar' => [$rule1, $rule2]]
        );
        $actualUriTemplate = $this->compiler->compile(null, '/foo/:bar(dave("1,2"),alex(3, 4))');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingRouteVarWithSingleRuleCreatesCorrectTemplate(): void
    {
        $rule = $this->createMock(IRule::class);
        $this->ruleFactory->expects($this->once())
            ->method('createRule')
            ->with('dave')
            ->willReturn($rule);
        $variableNode = new Node(NodeTypes::VARIABLE, 'bar');
        $variableNode->addChild(new Node(NodeTypes::VARIABLE_RULE, 'dave'));
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/'));
        $ast->getCurrentNode()
            ->addChild($variableNode);
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate(
            "^[^/]+/foo/([^\/\.]+)$",
            false,
            ['bar'],
            false,
            [],
            ['bar' => [$rule]]
        );
        $actualUriTemplate = $this->compiler->compile(null, '/foo/:bar(dave)');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }

    public function testCompilingRouteVarWithSingleWithParamsRuleCreatesCorrectTemplate(): void
    {
        $rule = $this->createMock(IRule::class);
        $this->ruleFactory->expects($this->once())
            ->method('createRule')
            ->with('dave', ['alex', 'lindsey'])
            ->willReturn($rule);
        $daveRuleNode = new Node(NodeTypes::VARIABLE_RULE, 'dave');
        $daveRuleNode->addChild(new Node(NodeTypes::VARIABLE_RULE_PARAMETERS, ['alex', 'lindsey']));
        $variableNode = new Node(NodeTypes::VARIABLE, 'bar');
        $variableNode->addChild($daveRuleNode);
        $ast = new AbstractSyntaxTree();
        $ast->getCurrentNode()
            ->addChild(new Node(NodeTypes::TEXT, '/foo/'));
        $ast->getCurrentNode()
            ->addChild($variableNode);
        $this->parser->expects($this->once())
            ->method('parse')
            ->willReturn($ast);
        $expectedUriTemplate = new UriTemplate(
            "^[^/]+/foo/([^\/\.]+)$",
            false,
            ['bar'],
            false,
            [],
            ['bar' => [$rule]]
        );
        $actualUriTemplate = $this->compiler->compile(null, '/foo/:bar(dave("alex","lindsey"))');
        $this->assertEquals($expectedUriTemplate, $actualUriTemplate);
    }
}