## Creating controllers

You can extend the [`BaseApiController`](/src/TreeHouse/BaseApiBundle/Controller/BaseApiController.php)
to create actions very quickly. Here's a simple RESTful CRUD example:

```php
# src/Acme/ApiBundle/Controller/ArticleController.php

class ArticleController extends BaseApiController
{
    /**
     * @Route('/articles/')
     * @Secure(roles="ROLE_API_USER")
     */
    public function getArticles()
    {
        $articles = $this->doctrine->getRepository('AcmeApiBundle:Article')->findAll();

        return $this->renderOk($articles, 200);
    }

    /**
     * @Method({"GET"})
     * @Route('/articles/{id}', requirements={"id"="\d+"})
     * @Secure(roles="ROLE_API_USER")
     */
    public function getArticle($id)
    {
        if (null === $article = $this->doctrine->getRepository('AcmeApiBundle:Article')->find($id)) {
            return $this->renderOk(null, Response::HTTP_NOT_FOUND);
        }

        return $this->renderOk($article, Response::HTTP_OK);
    }

    /**
     * @Method({"GET"})
     * @Route('/articles/{id}', requirements={"id"="\d+"})
     * @Secure(roles="ROLE_API_USER")
     */
    public function getArticle($id)
    {
        if (null === $article = $this->doctrine->getRepository('AcmeApiBundle:Article')->find($id)) {
            return $this->renderOk(null, Response::HTTP_NOT_FOUND);
        }

        return $this->renderOk($article, Response::HTTP_OK);
    }

    /**
     * @Method({"POST"})
     * @Route('/articles/')
     * @Secure(roles="ROLE_API_USER")
     */
    public function postArticle(Request $request)
    {
        $article = $this->getRequestData($request, 'Acme\ApiBundle\Entity\Article');

        try {
            $this->validate($article);
            $this->getDoctrine()->getManager()->persist($article);
            $this->getDoctrine()->getManager()->flush($article);
        } catch (ValidationException $e) {
            return $this->renderError(Response::HTTP_BAD_REQUEST, $e->getViolations());
        } catch (\Exception $e) {
            return $this->renderError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return $this->renderOk($article, Response::HTTP_CREATED);
    }

    /**
     * @Method({"PUT"})
     * @Route('/articles/{id}', requirements={"id"="\d+"})
     * @Secure(roles="ROLE_API_USER")
     */
    public function putArticle(Request $request)
    {
        if (null === $article = $this->doctrine->getRepository('AcmeApiBundle:Article')->find($id)) {
            return $this->renderOk(null, Response::HTTP_NOT_FOUND);
        }

        $update = $this->getRequestData($request, 'Acme\ApiBundle\Entity\Article');

        $this->merge($article, $update);


        try {
            $this->validate($article);
            $this->doctrine->getManager()->flush($article);
        } catch (ValidationException $e) {
            return $this->renderError(Response::HTTP_BAD_REQUEST, $e->getViolations());
        } catch (\Exception $e) {
            return $this->renderError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return $this->renderOk($article, Response::HTTP_OK);
    }

    /**
     * @Method({"DELETE"})
     * @Route('/articles/')
     * @Secure(roles="ROLE_API_USER")
     */
    public function deleteArticle(Request $request)
    {
        if (null === $article = $this->doctrine->getRepository('AcmeApiBundle:Article')->find($id)) {
            return $this->renderOk(null, Response::HTTP_NOT_FOUND);
        }

        try {
            $this->getDoctrine()->getManager()->remove($article);
            $this->getDoctrine()->getManager()->flush($article);
        } catch (\Exception $e) {
            return $this->renderError(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return $this->renderOk(null, Response::HTTP_OK);
    }
}

```