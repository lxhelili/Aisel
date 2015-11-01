<?php

/*
 * This file is part of the Aisel package.
 *
 * (c) Ivan Proskuryakov
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aisel\ReviewBundle\Tests\Controller\Admin;

use Aisel\ResourceBundle\Tests\AbstractBackendWebTestCase;
use Aisel\ReviewBundle\Document\Node;
use Aisel\ReviewBundle\Tests\ReviewWebTestCase;

/**
 * ApiNodeEditControllerTest
 *
 * @author Ivan Proskuryakov <volgodark@gmail.com>
 */
class ApiNodeEditControllerTest extends ReviewWebTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->logInBackend();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function createNode($name)
    {
        $node = new Node();
        $node->setLocale('en');
        $node->setDescription('');
        $node->setMetaUrl('/' . md5(rand(111111, 999999)));
        $node->setTitle($name);
        $this->dm->persist($node);
        $this->dm->flush();

        return $node;
    }

    public function testReviewNodeUpdateParentAction()
    {
        $parent = $this->createNode('Parent' . rand(1111, 9999));
        $child = $this->createNode('Child' . rand(1111, 9999));

        $this->client->request(
            'GET',
            '/' . $this->api['backend'] . '/review/node/node/' .
            '?locale=en&action=dragDrop' .
            '&id=' . $child->getId() .
            '&parentId=' . $parent->getId() . '',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();

        $statusCode = $response->getStatusCode();
        $result = json_decode($content, true);

        $this->assertTrue(200 === $statusCode);
        $this->assertEquals($result['parent']['id'], $parent->getId());

        $this->dm->clear();

        $node = $this
            ->dm
            ->getRepository('Aisel\ReviewBundle\Document\Node')
            ->findOneBy(['id' => $result['id']]);

        $this->assertEquals($parent->getId(), $node->getParent()->getId());
        $this->assertEquals($child->getId(), $node->getParent()->getChildren()[0]->getId());
    }

    public function testReviewNodeAddChildAction()
    {
        $parent = $this->createNode('AAA');

        $this->client->request(
            'GET',
            '/' . $this->api['backend'] . '/review/node/node/' .
            '?locale=en' .
            '&action=addChild' .
            '&name=New+children' .
            '&parentId=' . $parent->getId() . '',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $result = json_decode($content, true);

        $this->assertTrue(200 === $statusCode);
        $this->assertEquals($result['parent']['id'], $parent->getId());

        $this->dm->clear();

        $parent = $this
            ->dm
            ->getRepository('Aisel\ReviewBundle\Document\Node')
            ->findOneBy(['id' => $parent->getId()]);

        $node = $this
            ->dm
            ->getRepository('Aisel\ReviewBundle\Document\Node')
            ->findOneBy(['id' => $result['id']]);
        $this->assertEquals($node->getParent()->getId(), $parent->getId());
        $this->assertEquals($node->getId(), $parent->getChildren()[0]->getId());
    }

    public function testReviewNodeChangeTitleAction()
    {

        $node = $this->createNode('AAA');

        $this->client->request(
            'GET',
            '/' . $this->api['backend'] . '/review/node/node/' .
            '?locale=en' .
            '&action=save' .
            '&name=BBB' .
            '&id=' . $node->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $result = json_decode($content, true);

        $this->assertTrue(200 === $statusCode);
        $this->assertEquals($result['title'], 'BBB');
    }

    public function testReviewNodeDeleteAction()
    {
        $node = $this->createNode('DeleteMe');

        $this->client->request(
            'GET',
            '/' . $this->api['backend'] . '/review/node/node/' .
            '?locale=en' .
            '&action=remove' .
            '&id=' . $node->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        $node = $this
            ->dm
            ->getRepository('Aisel\ReviewBundle\Document\Node')
            ->findOneBy(['title' => 'ZZZZ']);

        $this->assertTrue(200 === $statusCode);
        $this->assertNull($node);
    }

}