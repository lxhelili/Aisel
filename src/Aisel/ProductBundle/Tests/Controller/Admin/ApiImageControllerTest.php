<?php

/*
 * This file is part of the Aisel package.
 *
 * (c) Ivan Proskuryakov
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Aisel\ProductBundle\Tests\Controller\Admin;

use Aisel\MediaBundle\Tests\Controller\UploadControllerTest;

/**
 * ApiImageControllerTest
 *
 * @author Ivan Proskuryakov <volgodark@gmail.com>
 */
class ApiImageControllerTest extends UploadControllerTest
{

    public function testPostImageAction()
    {
        $images = [];
        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'ru']);

        foreach ($product->getImages() as $image) {
            $this->dm->remove($image);
            $this->dm->flush();
        }

        foreach ($this->filenames['files'] as $file) {
            $filename = $this->upload($product->getId(), $file);

            // Create Product Image entity
            $data = [
                'filename' => $filename,
                'title' => 'title',
                'description' => 'description',
            ];

            $this->client->request(
                'POST',
                '/' . $this->api['backend'] .
                '/media/image/',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($data)
            );

            $response = $this->client->getResponse();
            $content = $response->getContent();

            $statusCode = $response->getStatusCode();
            $result = json_decode($content, true);

            $parts = explode('/', $response->headers->get('location'));
            $id = array_pop($parts);
            $this->assertEquals($statusCode, 201);
            $this->assertEquals($result, '');
            $this->assertNotNull($id);

            $images[] = $id;
        }

        // Patching product
        $data = [
            'id' => $product->getId(),
            'images' => $images,
        ];

        $this->client->request(
            'PUT',
            '/'. $this->api['backend'] . '/product/' . $product->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();

        $this->assertEmpty($content);
        $this->assertTrue(204 === $statusCode);

        $this->dm->flush();

        //Checking
        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'en']);

        $this->assertEquals(count($this->filenames['files']), count($product->getImages()));
    }

    public function testPutImageAction()
    {
        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'en']);

        $this->assertEquals(count($this->filenames['files']), count($product->getImages()));

        $filename = $this->upload($product->getId(), $this->filenames['files'][0]);

        $data = [
            'title' => 'title',
            'description' => 'description',
            'filename' => $filename
        ];

        $image = $product->getImages()[0];

        var_dump($image->getId());
        exit();

        $this->client->request(
            'PUT',
            '/' . $this->api['backend'] .
            '/media/image/' . $image->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $response = $this->client->getResponse();
        $content = $response->getContent();
        $statusCode = $response->getStatusCode();
        $result = json_decode($content, true);

        $this->assertEquals($statusCode, 204);
        $this->assertEquals($result, '');

        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'en']);

        foreach ($product->getImages() as $image) {
            $this->assertEquals($data['title'], $image->getTitle());
            $this->assertEquals($data['description'], $image->getDescription());
        }

    }

    public function testGetImageAction()
    {
        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'en']);

        $this->assertEquals(count($this->filenames['files']), count($product->getImages()));

        foreach ($product->getImages() as $image) {
            $this->client->request(
                'GET',
                '/' . $this->api['backend'] .
                '/media/image/' . $image->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json']
            );

            $response = $this->client->getResponse();
            $content = $response->getContent();
            $statusCode = $response->getStatusCode();
            $result = json_decode($content, true);

            $this->assertEquals($statusCode, 200);
            $this->assertNotNull($result['id'], $product->getId());
            $this->assertNotNull($result['filename']);
            $this->assertNotNull($result['title']);
            $this->assertNotNull($result['description']);
            $this->assertNotNull($result['main_image']);
            $this->assertNotNull($result['updated_at']);
            $this->assertNotNull($result['updated_at']);
        }
    }

    public function testDeleteImageAction()
    {
        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'en']);

        $this->assertEquals(count($this->filenames['files']), count($product->getImages()));

        foreach ($product->getImages() as $image) {
            $this->client->request(
                'DELETE',
                '/' . $this->api['backend'] .
                '/media/image/' . $image->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json']
            );
        }

        $product = $this
            ->dm
            ->getRepository('Aisel\ProductBundle\Document\Product')
            ->findOneBy(['locale' => 'en']);

        $this->assertEquals(0, count($product->getImages()->toArray()));
    }

}
