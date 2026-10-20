<?php

declare(strict_types=1);

namespace Brightspace\Api\Tests\Outcome\Model;

use Brightspace\Api\Outcome\Model\OutcomeDetail;
use Brightspace\Api\Outcome\Model\OutcomeRegistry;
use PHPUnit\Framework\TestCase;

final class OutcomeRegistryTest extends TestCase
{
    /**
     * Builds a registry from a nested map of id => children ids.
     *
     * @param string $id
     * @param array<string,array<string,mixed>> $tree
     * @return OutcomeRegistry
     */
    private static function registry(string $id, array $tree): OutcomeRegistry
    {
        return new OutcomeRegistry($id, self::outcomes($tree));
    }


    /**
     * @param array<string,array<string,mixed>> $tree
     * @return OutcomeDetail[]
     */
    private static function outcomes(array $tree): array
    {
        $outcomes = [];
        foreach ($tree as $id => $children) {
            /** @var array<string,array<string,mixed>> $children */
            $outcomes[] = new OutcomeDetail($id, 'Outcome ' . $id, self::outcomes($children));
        }
        return $outcomes;
    }


    /**
     * Flattens a registry back into the nested map form for comparison.
     *
     * @param OutcomeDetail[] $outcomes
     * @return array<string,array<string,mixed>>
     */
    private static function shape(array $outcomes): array
    {
        $shape = [];
        foreach ($outcomes as $outcome) {
            $shape[$outcome->id] = self::shape($outcome->children);
        }
        return $shape;
    }


    public function testCreateMapsTheIdAndObjectives(): void
    {
        $registry = OutcomeRegistry::create([
            'id' => 'reg-1',
            'objectives' => [
                ['id' => 'a', 'description' => 'A', 'children' => [['id' => 'a1', 'description' => 'A1']]],
            ],
        ]);

        self::assertSame('reg-1', $registry->id);
        self::assertSame(['a' => ['a1' => []]], self::shape($registry->objectives));
        self::assertSame('a', $registry->objectives[0]->children[0]->parentId);
    }


    public function testCreateWithoutObjectivesIsEmpty(): void
    {
        self::assertSame([], OutcomeRegistry::create(['id' => 'reg-1'])->objectives);
    }


    public function testMergeAddsOutcomesTheRegistryDoesNotHave(): void
    {
        $target = self::registry('target', ['a' => []]);
        $source = self::registry('source', ['a' => [], 'b' => []]);

        self::assertTrue($target->merge($source));

        self::assertSame(['a' => [], 'b' => []], self::shape($target->objectives));
    }


    public function testMergeAddsMissingChildrenUnderAnOutcomeBothHave(): void
    {
        $target = self::registry('target', ['a' => ['a1' => []]]);
        $source = self::registry('source', ['a' => ['a2' => ['a2x' => []]]]);

        self::assertTrue($target->merge($source));

        self::assertSame(['a' => ['a1' => [], 'a2' => ['a2x' => []]]], self::shape($target->objectives));
    }


    public function testMergeReportsNothingChangedWhenTheSourceAddsNothing(): void
    {
        $target = self::registry('target', ['a' => ['a1' => []], 'b' => []]);
        $source = self::registry('source', ['a' => ['a1' => []]]);

        self::assertFalse($target->merge($source));

        self::assertSame(['a' => ['a1' => []], 'b' => []], self::shape($target->objectives));
    }


    public function testMergeLeavesBothObjectiveListsAsPlainLists(): void
    {
        $target = self::registry('target', ['a' => ['a1' => []]]);
        $source = self::registry('source', ['a' => ['a2' => []], 'b' => []]);

        $target->merge($source);

        self::assertSame([0, 1], array_keys($target->objectives));
        self::assertSame([0, 1], array_keys($target->objectives[0]->children));
        self::assertSame([0, 1], array_keys($source->objectives));
        self::assertSame([0], array_keys($source->objectives[0]->children));
    }


    public function testMergeWrapsAFailureWithBothRegistryIds(): void
    {
        $target = self::registry('target', []);
        $broken = new OutcomeRegistry('source', ['not an outcome']); // @phpstan-ignore argument.type

        try {
            $target->merge($broken);
            self::fail('Merging a registry whose objectives are not OutcomeDetails should fail');
        } catch (\RuntimeException $e) {
            self::assertSame('Error merging source into target', $e->getMessage());
            self::assertInstanceOf(\Throwable::class, $e->getPrevious());
        }
    }


    public function testEqualsIsTrueForTheSameTreeInAnyOrder(): void
    {
        $left = self::registry('left', ['a' => ['a1' => [], 'a2' => []], 'b' => []]);
        $right = self::registry('right', ['b' => [], 'a' => ['a2' => [], 'a1' => []]]);

        self::assertTrue($left->equals($right));
        self::assertTrue($right->equals($left));
    }


    public function testEqualsIsFalseWhenAnOutcomeOrChildDiffers(): void
    {
        $base = self::registry('base', ['a' => ['a1' => []]]);

        self::assertFalse($base->equals(self::registry('extra', ['a' => ['a1' => []], 'b' => []])));
        self::assertFalse($base->equals(self::registry('renamed', ['c' => ['a1' => []]])));
        self::assertFalse($base->equals(self::registry('deeper', ['a' => ['a1' => [], 'a2' => []]])));
        self::assertFalse($base->equals(self::registry('shallower', ['a' => []])));
    }


    public function testEqualsDoesNotReindexTheObjectives(): void
    {
        $left = self::registry('left', ['a' => ['a1' => []], 'b' => []]);
        $right = self::registry('right', ['a' => ['a1' => []], 'b' => []]);

        $left->equals($right);

        self::assertSame([0, 1], array_keys($left->objectives));
        self::assertSame([0], array_keys($left->objectives[0]->children));
        self::assertSame([0, 1], array_keys($right->objectives));
        self::assertSame([0], array_keys($right->objectives[0]->children));
    }
}
