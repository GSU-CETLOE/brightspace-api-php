<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Outcome\Model;

use Brightspace\Api\Outcome\Model\OutcomeDetail;
use PHPUnit\Framework\TestCase;

final class OutcomeDetailTest extends TestCase
{
    public function testCreateMapsTheTreeAndPointsEachChildAtItsParent(): void
    {
        $outcome = OutcomeDetail::create([
            'id' => 'root',
            'description' => 'Top level',
            'children' => [
                ['id' => 'child-1', 'description' => 'First', 'children' => [
                    ['id' => 'grandchild', 'description' => 'Deep'],
                ]],
                ['id' => 'child-2', 'description' => 'Second'],
            ],
        ]);

        self::assertSame('root', $outcome->id);
        self::assertSame('Top level', $outcome->description);
        self::assertNull($outcome->parentId);
        self::assertCount(2, $outcome->children);
        self::assertSame('child-1', $outcome->children[0]->id);
        self::assertSame('root', $outcome->children[0]->parentId);
        self::assertSame('root', $outcome->children[1]->parentId);
        self::assertSame('child-1', $outcome->children[0]->children[0]->parentId);
    }


    public function testCreateKeepsAParentIdItIsGiven(): void
    {
        $outcome = OutcomeDetail::create(['id' => 'x', 'description' => 'Loose', 'parentId' => 'elsewhere']);

        self::assertSame('elsewhere', $outcome->parentId);
    }


    public function testSerializesOnlyTheIdAndChildrenAsAList(): void
    {
        $outcome = new OutcomeDetail('root', 'Top level', [
            'b' => new OutcomeDetail('b', 'B'),
            'a' => new OutcomeDetail('a', 'A'),
        ]);

        self::assertSame(
            '{"id":"root","children":[{"id":"b","children":[]},{"id":"a","children":[]}]}',
            json_encode($outcome)
        );
    }
}
