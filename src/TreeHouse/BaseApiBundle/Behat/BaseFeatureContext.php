<?php

namespace TreeHouse\BaseApiBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Feature context.
 */
abstract class BaseFeatureContext implements SnippetAcceptingContext, KernelAwareContext
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var Response
     */
    protected static $response;

    /**
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Builds schema for all Doctrine's managers
     *
     * @return void
     */
    public function buildSchemas()
    {
        /** @var EntityManagerInterface[] $managers */
        $managers = $this->get('doctrine')->getManagers();
        foreach ($managers as $manager) {
            $metadata = $manager->getMetadataFactory()->getAllMetadata();

            if (!empty($metadata)) {
                $tool = new SchemaTool($manager);
                $tool->dropSchema($metadata);
                $tool->createSchema($metadata);
            }
        }
    }

    /**
     * Drops all databases
     *
     * @return void
     */
    public function dropDatabases()
    {
        /** @var EntityManagerInterface[] $managers */
        $managers = $this->get('doctrine')->getManagers();
        foreach ($managers as $manager) {
            $tool = new SchemaTool($manager);
            $tool->dropDatabase();
        }
    }

    /**
     * Closes all open database connections
     *
     * @return void
     */
    public function closeDbalConnections()
    {
        /** @var EntityManagerInterface[] $managers */
        $managers = $this->get('doctrine')->getManagers();
        foreach ($managers as $manager) {
            $manager->clear();
            $manager->getConnection()->close();
        }
    }

    /**
     * @param string       $method
     * @param string       $uri
     * @param string|array $data
     * @param array        $headers
     * @param array        $server
     *
     * @return Response
     */
    protected function request($method, $uri, $data = null, array $headers = [], array $server = [])
    {
        if (!array_key_exists('HTTP_HOST', $server)) {
            $server['HTTP_HOST'] = $this->getContainer()->getParameter('fp_base_api.api_host');
        }

        $client = $this->createClient($server);

        foreach ($headers as $headerKey => $headerValue) {
            $server['HTTP_' . $headerKey] = $headerValue;
        }

        $client->request($method, '/' . ltrim($uri, '/'), [], [], $server, $data);

        return static::$response = $client->getResponse();
    }

    /**
     * Creates a Client.
     *
     * @param array $server An array of server parameters
     *
     * @return Client A Client instance
     */
    protected function createClient(array $server = [])
    {
        $client = $this->get('test.client');

        $client->setServerParameters($server);
        $client->followRedirects();

        return $client;
    }

    /**
     * @param string $entityName
     * @param array  $rows
     */
    protected function persistEntities($entityName, array $rows)
    {
        /** @var EntityManager $doctrine */
        $doctrine = $this->get('doctrine')->getManager();
        $meta = $doctrine->getClassMetadata($entityName);
        $meta->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

        $class = $meta->getName();

        foreach ($rows as $id => $row) {
            // try to find existing entity with this id
            if (null === $entity = $doctrine->getRepository($entityName)->find($id)) {
                // create a new one
                $entity = new $class();

                // use reflection to set the id (we don't have a setter for this)
                $reflectionClass    = $meta->getReflectionClass();
                $reflectionProperty = $reflectionClass->getProperty('id');
                $reflectionProperty->setAccessible(true);
                $reflectionProperty->setValue($entity, $id);

                $doctrine->persist($entity);
            }

            $accessor = PropertyAccess::createPropertyAccessor();
            foreach ($row as $field => $value) {
                $accessor->setValue($entity, $field, $value);
            }

            $doctrine->persist($entity);
            $doctrine->flush();
        }

        $doctrine->clear();
    }

    /**
     * Returns the content of the current response. File responses will be read first.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function getResponseContent()
    {
        if (!static::$response instanceof Response) {
            throw new \RuntimeException('No response available');
        }

        $content = static::$response->getContent();
        if (static::$response instanceof BinaryFileResponse) {
            $content = file_get_contents(static::$response->getFile()->getPathname());
        }

        return $content;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * @param string $service
     *
     * @return object
     */
    protected function get($service)
    {
        return $this->getContainer()->get($service);
    }
}
