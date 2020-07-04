<?php

namespace App\Tests\unit\Service;

use App\Service\BookMaker;
use App\Tests\UnitTester;
use PhpPact\Consumer\InteractionBuilder;
use PhpPact\Consumer\Model\ConsumerRequest;
use PhpPact\Consumer\Model\ProviderResponse;
use PhpPact\Consumer\Matcher\Matcher;
use PhpPact\Standalone\MockService\MockServerEnvConfig;

class BookMakerCest
{
    protected Matcher $matcher;
    protected MockServerEnvConfig $config;
    protected InteractionBuilder $builder;
    protected object $book;
    protected string $bookIri;

    public function _before(UnitTester $I)
    {
        $this->config = new MockServerEnvConfig();
        $this->builder = new InteractionBuilder($this->config);

        $this->matcher = new Matcher();

        $review = new \stdClass();
        $review->{'@id'} = $this->matcher->term('/api/reviews/fb5a885f-f7e8-4a50-950f-c1a64a94d500', '\/api\/reviews\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}');
        $review->{'@type'} = 'http://schema.org/Review';
        $review->body = $this->matcher->like('Necessitatibus eius commodi odio ut aliquid. Sit enim molestias in minus aliquid repudiandae qui. Distinctio modi officiis eos suscipit. Vel ut modi quia recusandae qui eligendi. Voluptas totam asperiores ab tenetur voluptatem repudiandae reiciendis.');

        $this->bookIri = '/api/books/0114b2a8-3347-49d8-ad99-0e792c5a30e6';

        $book = new \stdClass();
        $book->{'@id'} = $this->matcher->term($this->bookIri, '\/api\/books\/[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}');
        $book->{'@type'} = 'Book';
        $book->title = $this->matcher->like('Voluptas et tempora repellat corporis excepturi.');
        $book->description = $this->matcher->like('Quaerat odit quia nisi accusantium natus voluptatem. Explicabo corporis eligendi ut ut sapiente ut qui quidem. Optio amet velit aut delectus. Sed alias asperiores perspiciatis deserunt omnis. Mollitia unde id in.');
        $book->author = $this->matcher->like('Melisa Kassulke');
        $book->publicationDate = $this->matcher->dateTimeISO8601('1999-02-13T00:00:00+07:00');
        $book->reviews = $this->matcher->eachLike($review);

        $this->book = $book;
    }

    public function testCreateBook(UnitTester $I)
    {
        $this->setUpCreatingBook();
        $this->setUpGeneratingCover();

        $service = new BookMaker();
        $service->setBaseUrl($this->config->getBaseUri());

        $result = $service->createBook();
        $I->assertTrue($result, "Let's make sure we created a book");

        $this->builder->verify();
    }

    protected function setUpCreatingBook(): void
    {
        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('POST')
            ->setPath('/api/books')
            ->addHeader('Content-Type', 'application/json')
            ->setBody([
                'isbn' => $this->matcher->like('0099740915'),
                'title' => $this->matcher->like("The Handmaid's Tale"),
                'description' => $this->matcher->like('Brilliantly conceived and executed, this powerful evocation of twenty-first century America gives full rein to Margaret Atwood\'s devastating irony, wit and astute perception.'),
                'author' => $this->matcher->like('Margaret Atwood'),
                'publicationDate' => $this->matcher->like('1985-07-31T00:00:00+00:00'),
            ]);

        // build the response
        $response = new ProviderResponse();
        $response
            ->setStatus(201)
            ->addHeader('Content-Type', 'application/ld+json; charset=utf-8')
            ->setBody([
                '@context' => '/api/contexts/Book',
            ] + (array) $this->book);

        $this->builder->given('Book Fixtures Loaded')
            ->uponReceiving('A POST request to create book')
            ->with($request)
            ->willRespondWith($response);
    }

    protected function setUpGeneratingCover(): void
    {
        // build the request
        $request = new ConsumerRequest();
        $request
            ->setMethod('PUT')
            ->setPath("{$this->bookIri}/generate-cover")
            ->addHeader('Content-Type', 'application/json')
            ->setBody([]);

        // build the response
        $response = new ProviderResponse();
        $response
            ->setStatus(204)
            ->addHeader('Content-Type', 'application/ld+json; charset=utf-8');

        $this->builder->given('Book Fixtures Loaded')
            ->uponReceiving('A PUT request to generate book cover')
            ->with($request)
            ->willRespondWith($response);
    }
}
