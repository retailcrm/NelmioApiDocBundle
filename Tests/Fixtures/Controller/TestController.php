<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Fixtures\Controller;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Tests\Fixtures\DependencyTypePath;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

class TestController
{
    /**
     * @ApiDoc(
     *     resource="TestResource",
     *     views="default"
     * )
     */
    public function namedResourceAction(): void
    {
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="index action",
     *  filters={
     *      {"name"="a", "dataType"="integer"},
     *      {"name"="b", "dataType"="string", "arbitrary"={"arg1", "arg2"}}
     *  }
     * )
     */
    public function indexAction()
    {
        return new Response('tests');
    }

    /**
     * @ApiDoc(
     *  resource=true,
     *  description="create test",
     *  views={ "default", "premium" },
     *  input="Nelmio\ApiDocBundle\Tests\Fixtures\Form\TestType"
     * )
     */
    public function postTestAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     description="post test 2",
     *     views={ "default", "premium" },
     *     resource=true
     * )
     */
    public function postTest2Action(): void
    {
    }

    /**
     * @ApiDoc(
     *  input="Nelmio\ApiDocBundle\Tests\Fixtures\Form\RequiredType"
     * )
     */
    public function requiredParametersAction(): void
    {
    }

    public function anotherAction(): void
    {
    }

    /**
     * @ApiDoc()
     */
    public function routeVersionAction(): void
    {
    }

    /**
     * @ApiDoc(description="Action without HTTP verb")
     */
    public function anyAction(): void
    {
    }

    /**
     * This method is useful to test if the getDocComment works.
     * And, it supports multilines until the first '@' char.
     *
     * @ApiDoc()
     *
     * @param int $id        A nice comment
     * @param int $page
     * @param int $paramType The param type
     * @param int $param     The param id
     */
    public function myCommentedAction($id, $page, int $paramType, int $param): void
    {
    }

    /**
     * @ApiDoc()
     */
    public function yetAnotherAction(): void
    {
    }

    /**
     * @ApiDoc(
     *  views= { "default", "test" },
     *  description="create another test",
     *  input=DependencyTypePath::TYPE
     * )
     */
    public function anotherPostAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(strict=true, name="page", requirements="\d+", description="Page of the overview.")
     */
    public function zActionWithQueryParamStrictAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page of the overview.")
     */
    public function zActionWithQueryParamAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(name="page", requirements="\d+", description="Page of the overview.")
     */
    public function zActionWithQueryParamNoDefaultAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(name="mail", requirements=@Assert\Email, description="Email of someone.")
     */
    public function zActionWithConstraintAsRequirements(): void
    {
    }

    /**
     * @ApiDoc(
     *  description="Testing JMS",
     *  input="Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest"
     * )
     */
    public function jmsInputTestAction(): void
    {
    }

    /**
     * @ApiDoc(
     *  description="Testing return",
     *  output=DependencyTypePath::TYPE
     * )
     */
    public function jmsReturnTestAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @RequestParam(name="param1", requirements="string", description="Param1 description.")
     */
    public function zActionWithRequestParamAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @RequestParam(name="param1", requirements="string", description="Param1 description.", nullable=true)
     */
    public function zActionWithNullableRequestParamAction(): void
    {
    }

    /**
     * @ApiDoc()
     */
    public function secureRouteAction(): void
    {
    }

    /**
     * @ApiDoc(
     *  authentication=true,
     *  authenticationRoles={"ROLE_USER","ROLE_FOOBAR"}
     * )
     */
    public function authenticatedAction(): void
    {
    }

    /**
     * @ApiDoc()
     */
    public function zCachedAction(): void
    {
    }

    /**
     * @ApiDoc()
     */
    public function zSecuredAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @deprecated
     */
    public function deprecatedAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     output="Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest"
     * )
     */
    public function jmsReturnNestedOutputAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     output="Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsChild"
     * )
     */
    public function jmsReturnNestedExtendedOutputAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     output="Nelmio\ApiDocBundle\Tests\Fixtures\Model\MultipleTest"
     * )
     */
    public function zReturnJmsAndValidationOutputAction(): void
    {
    }

    /**
     * @ApiDoc(
     *  description="Returns a collection of Object",
     *  requirements={
     *      {"name"="limit", "dataType"="integer", "requirement"="\d+", "description"="how many objects to return"}
     *  },
     *  parameters={
     *      {"name"="categoryId", "dataType"="integer", "required"=true, "description"="category id"}
     *  }
     * )
     */
    public function cgetAction($id): void
    {
    }

    /**
     * @ApiDoc(
     *     input={
     *          "class"="Nelmio\ApiDocBundle\Tests\Fixtures\Form\TestType",
     *          "parsers"={
     *              "Nelmio\ApiDocBundle\Parser\FormTypeParser",
     *          }
     *     }
     * )
     */
    public function zReturnSelectedParsersInputAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     output={
     *          "class"="Nelmio\ApiDocBundle\Tests\Fixtures\Model\MultipleTest",
     *          "parsers"={
     *              "Nelmio\ApiDocBundle\Parser\JmsMetadataParser",
     *              "Nelmio\ApiDocBundle\Parser\ValidationParser"
     *          }
     *     }
     * )
     */
    public function zReturnSelectedParsersOutputAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     section="private"
     * )
     */
    public function privateAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     section="exclusive"
     * )
     */
    public function exclusiveAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @see http://symfony.com
     */
    public function withLinkAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     output="Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest",
     *     input={
     *         "class" = "Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest"
     *     },
     *     parameters={
     *          {
     *              "name"="number",
     *              "dataType"="integer",
     *              "actualType"="string",
     *              "subType"=null,
     *              "required"=true,
     *              "description"="This is the new description",
     *              "readonly"=false,
     *              "sinceVersion"="v3.0",
     *              "untilVersion"="v4.0"
     *          },
     *          {
     *              "name"="arr",
     *              "dataType"="object (ArrayCollection)"
     *          },
     *          {
     *              "name"="nested",
     *              "dataType"="object (JmsNested)",
     *              "children": {
     *                  "bar": {
     *                      "dataType"="integer",
     *                      "format"="d+"
     *                  }
     *              }
     *          }
     *     }
     * )
     */
    public function overrideJmsAnnotationWithApiDocParametersAction(): void
    {
    }

    /**
     * @ApiDoc(
     *     output="Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest",
     *     input={
     *         "class" = "Nelmio\ApiDocBundle\Tests\Fixtures\Model\JmsTest"
     *     }
     * )
     */
    public function defaultJmsAnnotations(): void
    {
    }

    /**
     * @ApiDoc(
     *  description="Route with host placeholder",
     *  views={ "default" }
     * )
     */
    public function routeWithHostAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(name="param1", requirements={"rule": "regexp", "error_message": "warning"}, description="Param1 description.")
     */
    public function routeWithQueryParamArrayRequirementsAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(name="param1", requirements={@Assert\NotNull(), @Assert\NotBlank()}, description="Param1 description.")
     */
    public function routeWithQueryParamPlainArrayRequirementsAction(): void
    {
    }

    /**
     * @ApiDoc()
     *
     * @QueryParam(name="param1", description="Param1 description.")
     */
    public function zActionWithRequirementParamNotSet(): void
    {
    }
}
