<?php

namespace TreeHouse\BaseApiBundle\Behat;

use Behat\Gherkin\Node\TableNode;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserManagerInterface;
use PHPUnit_Framework_Assert as Assert;

class FixtureContext extends BaseFeatureContext
{
    /**
     * @Given an api user named :username with password :password exists
     */
    public function anApiUserWithNameAndPasswordExists($username, $password)
    {
        $user = $this->aUserWithNameAndPasswordExists($username, $password);
        $user->addRole('ROLE_API_USER');

        /** @var UserManagerInterface $userManager */
        $userManager = $this->get('fos_user.user_manager');
        $userManager->updateUser($user);

        return $user;
    }

    /**
     * @Given a user named :username with password :password exists
     */
    public function aUserWithNameAndPasswordExists($username, $password)
    {
        /** @var UserManagerInterface $userManager */
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->createUser();
        $user->setEnabled(true);
        $user->setUsername($username);
        $user->setEmail(sprintf('%s@example.org', $username));
        $user->setPlainPassword($password);
        $userManager->updateUser($user);

        return $user;
    }

    /**
     * @Given the following :entityName entities exist:
     */
    public function theFollowingEntitiesExist($entityName, TableNode $table)
    {
        /** @var EntityManager $doctrine */
        $doctrine = $this->get('doctrine')->getManager();
        $meta = $doctrine->getClassMetadata($entityName);

        $rows = [];
        $hash = $table->getHash();
        foreach ($hash as $row) {
            $id = $row['id'];
            unset($row['id']);

            foreach ($row as $property => &$value) {
                $propertyName = Inflector::camelize($property);

                $fieldType = $meta->getTypeOfField($propertyName);
                switch ($fieldType) {
                    case 'array':
                    case 'json_array':
                        $value = json_decode($value, true);
                        break;
                    case 'datetime':
                        $value = new \DateTime($value);
                        break;
                }
            }

            $rows[$id] = $row;
        }

        $this->persistEntities($entityName, $rows);
    }

    /**
     * @Given the entity :entityName with id :id does not exist
     */
    public function theEntityWithIdDoesNotExist($entityName, $id)
    {
        $entity = $this->get('doctrine')->getRepository($entityName)->findOneById($id);

        Assert::assertNull($entity, "Result of 'findOneById' should be NULL when an entity does not exist");
    }
}
