<?php

namespace NeuronAI\Tests\VectorStore;

use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use PHPUnit\Framework\TestCase;

class FileVectorStoreTest extends TestCase
{
    public function test_store_documents()
    {
        $document = new Document('Hello!');
        $document->embedding = [1, 2, 3];
        $document->id = 1;
        $document->chunkNumber = 1;
        $document->sourceName = 'test';
        $document->sourceType = 'string';

        $document2 = new Document('Hello 2!');
        $document2->embedding = [3, 4, 5];
        $document2->id = 2;
        $document2->chunkNumber = 2;
        $document2->sourceName = 'test';
        $document2->sourceType = 'string';

        $store = new FileVectorStore(__DIR__, 1);
        $store->addDocuments([$document, $document2]);

        $result = $store->similaritySearch([1, 2, 3]);

        $this->assertCount(1, $result);
        $this->assertEquals($document->id, $result[0]->id);
        $this->assertEquals($document->content, $result[0]->content);
        $this->assertEquals($document->embedding, $result[0]->embedding);
        $this->assertEquals($document->sourceType, $result[0]->sourceType);
        $this->assertEquals($document->sourceName, $result[0]->sourceName);

        unlink(__DIR__.'/neuron.store');
    }
}
