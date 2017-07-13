<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Sequencing\AbstractGenerator;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\StaticPHPDriver;
use Doctrine\ORM\Sequencing\Generator;

/**
 * @group DDC-2415
 */
class DDC2415Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->em->getConfiguration()->setMetadataDriverImpl(new StaticPHPDriver([]));

        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC2415ParentEntity::class),
            $this->em->getClassMetadata(DDC2415ChildEntity::class),
            ]
        );
    }

    public function testTicket()
    {
        $this->fail('FIXME');

        $parentMetadata  = $this->em->getClassMetadata(DDC2415ParentEntity::class);
        $childMetadata   = $this->em->getClassMetadata(DDC2415ChildEntity::class);

        self::assertEquals($parentMetadata->generatorType, $childMetadata->generatorType);
        self::assertEquals($parentMetadata->generatorDefinition, $childMetadata->generatorDefinition);
        self::assertEquals(DDC2415Generator::class, $parentMetadata->generatorDefinition['class']);

        $e1 = new DDC2415ChildEntity("ChildEntity 1");
        $e2 = new DDC2415ChildEntity("ChildEntity 2");

        $this->em->persist($e1);
        $this->em->persist($e2);
        $this->em->flush();
        $this->em->clear();

        self::assertEquals(md5($e1->getName()), $e1->getId());
        self::assertEquals(md5($e2->getName()), $e2->getId());
    }
}

class DDC2415ParentEntity
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setPrimaryKey(true);
        $fieldMetadata->setIdentifierGeneratorType(Mapping\GeneratorType::CUSTOM);
        $fieldMetadata->setIdentifierGeneratorDefinition(
            [
                'class'     => DDC2415Generator::class,
                'arguments' => [],
            ]
        );

        $metadata->addProperty($fieldMetadata);

        $metadata->isMappedSuperclass = true;
    }
}

class DDC2415ChildEntity extends DDC2415ParentEntity
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('name');

        $fieldMetadata->setType(Type::getType('string'));

        $metadata->addProperty($fieldMetadata);
    }
}

class DDC2415Generator implements Generator
{
    public function generate(EntityManager $em, $entity)
    {
        return md5($entity->getName());
    }

    public function isPostInsertGenerator()
    {
        return false;
    }
}
