<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use ExtbaseTeam\BlogExample\Domain\Model\Blog;
use ExtbaseTeam\BlogExample\Domain\Model\Comment;
use ExtbaseTeam\BlogExample\Domain\Model\DateExample;
use ExtbaseTeam\BlogExample\Domain\Model\DateTimeImmutableExample;
use ExtbaseTeam\BlogExample\Domain\Model\Post;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\Exception\UnknownPropertyTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestDataMapper\Domain\Model\CustomDateTime;
use TYPO3Tests\TestDataMapper\Domain\Model\Example;
use TYPO3Tests\TestDataMapper\Domain\Model\TraversableDomainObjectExample;

class DataMapperTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/test_data_mapper',
    ];

    protected PersistenceManager $persistenceManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->persistenceManager = $this->get(PersistenceManager::class);
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInUtcInIntegerDatabaseFields(): void
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
        $example->setDatetimeInt($date);

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($example);
        $this->persistenceManager->clearState();

        /** @var DateExample $example */
        $example = $this->persistenceManager->getObjectByIdentifier($uid, DateExample::class);

        self::assertEquals($example->getDatetimeInt()->getTimestamp(), $date->getTimestamp());
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInUtcInTextDatabaseFields(): void
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00+01:00');
        $example->setDatetimeText($date);

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($example);
        $this->persistenceManager->clearState();

        /** @var DateExample $example */
        $example = $this->persistenceManager->getObjectByIdentifier($uid, DateExample::class);

        self::assertEquals($example->getDatetimeText()->getTimestamp(), $date->getTimestamp());
    }

    /**
     * @test
     */
    public function dateValuesAreStoredInLocalTimeInDatetimeDatabaseFields(): void
    {
        $example = new DateExample();
        $date = new \DateTime('2016-03-06T12:40:00');
        $example->setDatetimeDatetime($date);

        $this->persistenceManager->add($example);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($example);
        $this->persistenceManager->clearState();

        /** @var DateExample $example */
        $example = $this->persistenceManager->getObjectByIdentifier($uid, DateExample::class);

        self::assertEquals($example->getDatetimeDatetime()->getTimestamp(), $date->getTimestamp());
    }

    /**
     * @test
     */
    public function dateTimeImmutableIntIsHandledAsDateTime(): void
    {
        $subject = new DateTimeImmutableExample();
        $date = new \DateTimeImmutable('2018-07-24T20:40:00');
        $subject->setDatetimeImmutableInt($date);

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($subject);
        $this->persistenceManager->clearState();

        /** @var DateTimeImmutableExample $subject */
        $subject = $this->persistenceManager->getObjectByIdentifier($uid, DateTimeImmutableExample::class);

        self::assertEquals($date, $subject->getDatetimeImmutableInt());
    }

    /**
     * @test
     */
    public function dateTimeImmutableTextIsHandledAsDateTime(): void
    {
        $subject = new DateTimeImmutableExample();
        $date = new \DateTimeImmutable('2018-07-24T20:40:00');
        $subject->setDatetimeImmutableText($date);

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($subject);
        $this->persistenceManager->clearState();

        /** @var DateTimeImmutableExample $subject */
        $subject = $this->persistenceManager->getObjectByIdentifier($uid, DateTimeImmutableExample::class);

        self::assertEquals($date, $subject->getDatetimeImmutableText());
    }

    /**
     * @test
     */
    public function dateTimeImmutableDateTimeIsHandledAsDateTime(): void
    {
        $subject = new DateTimeImmutableExample();
        $date = new \DateTimeImmutable('2018-07-24T20:40:00');
        $subject->setDatetimeImmutableDatetime($date);

        $this->persistenceManager->add($subject);
        $this->persistenceManager->persistAll();
        $uid = $this->persistenceManager->getIdentifierByObject($subject);
        $this->persistenceManager->clearState();

        /** @var DateTimeImmutableExample $subject */
        $subject = $this->persistenceManager->getObjectByIdentifier($uid, DateTimeImmutableExample::class);

        self::assertSame($date->getTimestamp(), $subject->getDatetimeImmutableDatetime()->getTimestamp());
    }

    /**
     * @test
     */
    public function mapMapsArrayToObject(): void
    {
        $rows = [['uid' => 1234]];
        $dataMapper = $this->get(DataMapper::class);

        $mappedObjectArray = $dataMapper->map(Blog::class, $rows);

        self::assertCount(1, $mappedObjectArray);
        self::assertSame(1234, $mappedObjectArray[0]->getUid());
    }

    /**
     * @test
     */
    public function mapMapsArrayToObjectFromPersistence(): void
    {
        $rows1 = [['uid' => 1234, 'title' => 'From persistence']];
        $rows2 = [['uid' => 1234, 'title' => 'Already persisted']];
        $dataMapper = $this->get(DataMapper::class);

        $dataMapper->map(Blog::class, $rows1);
        $mappedObjectArray = $dataMapper->map(Blog::class, $rows2);

        self::assertCount(1, $mappedObjectArray);
        self::assertSame(1234, $mappedObjectArray[0]->getUid());
        self::assertSame('From persistence', $mappedObjectArray[0]->getTitle());
    }

    /**
     * @test
     */
    public function thawPropertiesSetsPropertyValues(): void
    {
        $rows = [
            [
                'uid' => '1234',
                'first_property' => 'firstValue',
                'second_property' => 1234,
                'third_property' => 1.234,
                'fourth_property' => false,
                'uninitialized_string_property' => 'foo',
                'uninitialized_date_time_property' => 0,
                'uninitialized_mandatory_date_time_property' => 0,
                'initialized_date_time_property' => 0,
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);

        $mappedObjectsArray = $dataMapper->map(Example::class, $rows);

        /** @var Example $object */
        $object = $mappedObjectsArray[0];
        self::assertEquals('firstValue', $object->getFirstProperty());
        self::assertEquals(1234, $object->getSecondProperty());
        self::assertEquals(1.234, $object->getThirdProperty());
        self::assertFalse($object->isFourthProperty());
        self::assertSame('foo', $object->getUninitializedStringProperty());
        self::assertNull($object->getUninitializedDateTimeProperty());
        $reflectionProperty = new \ReflectionProperty($object, 'uninitializedMandatoryDateTimeProperty');
        self::assertFalse($reflectionProperty->isInitialized($object));
        $reflectionProperty = new \ReflectionProperty($object, 'initializedDateTimeProperty');
        self::assertTrue($reflectionProperty->isInitialized($object));
    }

    /**
     * @test
     */
    public function thawPropertiesThrowsExceptionOnUnknownPropertyType(): void
    {
        $rows = [
            [
                'uid' => '1234',
                'unknown_type' => 'What am I?',
            ],
        ];

        $dataMapper = $this->get(DataMapper::class);

        $this->expectException(UnknownPropertyTypeException::class);
        $dataMapper->map(Example::class, $rows);
    }

    /**
     * @test
     */
    public function fetchRelatedEagerReturnsNullForEmptyRelationHasOne(): void
    {
        $rows = [
            [
                'uid' => 123,
                'blog' => '',
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);
        $mappedObjectsArray = $dataMapper->map(Post::class, $rows);

        self::assertNull($mappedObjectsArray[0]->getBlog());
    }

    /**
     * @test
     */
    public function fetchRelatedEagerReturnsEmptyArrayForEmptyRelationNotHasOne(): void
    {
        $rows = [
            [
                'uid' => 123,
                'posts' => 0,
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);
        $mappedObjectsArray = $dataMapper->map(Blog::class, $rows);

        self::assertCount(0, $mappedObjectsArray[0]->getPosts());
    }

    /**
     * @test
     */
    public function mapObjectToClassPropertyReturnsExistingObjectWithoutCallingFetchRelated(): void
    {
        $blogRows = [
            [
                'uid' => 123,
                'posts' => 1,
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);
        $dataMapper->map(Blog::class, $blogRows);
        $postRows = [
            [
                'uid' => 234,
                'blog' => 123,
            ],
        ];

        $mappedObjectsArray = $dataMapper->map(Post::class, $postRows);

        self::assertSame(123, $mappedObjectsArray[0]->getBlog()->getUid());
    }

    /**
     * Data provider for date checks. Date will be stored based on UTC in
     * the database. That's why it's not possible to check for explicit date
     * strings but using the date('c') conversion instead, which considers the
     * current local timezone setting.
     */
    public static function mapDateTimeHandlesDifferentFieldEvaluationsDataProvider(): array
    {
        return [
            'nothing' => [null, null, null],
            'timestamp' => [1, null, date('c', 1)],
            'invalid date' => ['0000-00-00', 'date', null],
            'valid date' => ['2013-01-01', 'date', date('c', strtotime('2013-01-01 00:00:00'))],
            'invalid datetime' => ['0000-00-00 00:00:00', 'datetime', null],
            'valid datetime' => ['2013-01-01 01:02:03', 'datetime', date('c', strtotime('2013-01-01 01:02:03'))],
        ];
    }

    /**
     * @test
     * @dataProvider mapDateTimeHandlesDifferentFieldEvaluationsDataProvider
     */
    public function mapDateTimeHandlesDifferentFieldEvaluations(string|int|null $value, string|null $storageFormat, string|null $expectedValue): void
    {
        $GLOBALS['TCA']['tx_testdatamapper_domain_model_example']['columns']['initialized_date_time_property']['config']['dbType'] = $storageFormat;
        $rows = [
            [
                'uid' => 123,
                'initialized_date_time_property' => $value,
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);
        $mappedObjectsArray = $dataMapper->map(Example::class, $rows);

        self::assertSame($expectedValue, $mappedObjectsArray[0]->getInitializedDateTimeProperty()?->format('c'));

        // Flush DataMapFactory cache on each run.
        $cacheManager = $this->get(CacheManager::class);
        $cacheManager->getCache('extbase')->flush();
    }

    public static function mapDateTimeHandlesDifferentFieldEvaluationsWithTimeZoneDataProvider(): array
    {
        return [
            'nothing' => [null, null, null],
            'timestamp' => [1, null, '@1'],
            'invalid date' => ['0000-00-00', 'date', null],
            'valid date' => ['2013-01-01', 'date', '2013-01-01T00:00:00'],
            'invalid datetime' => ['0000-00-00 00:00:00', 'datetime', null],
            'valid datetime' => ['2013-01-01 01:02:03', 'datetime', '2013-01-01T01:02:03'],
        ];
    }

    /**
     * @test
     * @dataProvider mapDateTimeHandlesDifferentFieldEvaluationsWithTimeZoneDataProvider
     */
    public function mapDateTimeHandlesDifferentFieldEvaluationsWithTimeZone(string|int|null $value, ?string $storageFormat, ?string $expectedValue): void
    {
        $originalTimeZone = date_default_timezone_get();
        date_default_timezone_set('America/Chicago');
        $usedTimeZone = date_default_timezone_get();

        $GLOBALS['TCA']['tx_testdatamapper_domain_model_example']['columns']['initialized_date_time_property']['config']['dbType'] = $storageFormat;
        $rows = [
            [
                'uid' => 123,
                'initialized_date_time_property' => $value,
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);
        $mappedObjectsArray = $dataMapper->map(Example::class, $rows);

        $expectedValue = $expectedValue !== null ? new \DateTime($expectedValue, new \DateTimeZone($usedTimeZone)) : $expectedValue;
        self::assertEquals($expectedValue, $mappedObjectsArray[0]->getInitializedDateTimeProperty());

        // Flush DataMapFactory cache on each run.
        $cacheManager = $this->get(CacheManager::class);
        $cacheManager->getCache('extbase')->flush();

        // Restore the systems current timezone
        date_default_timezone_set($originalTimeZone);
    }

    /**
     * @test
     */
    public function mapDateTimeHandlesSubclassesOfDateTime(): void
    {
        $rows = [
            [
                'uid' => 123,
                'custom_date_time' => '2013-01-01 01:02:03',
            ],
        ];
        $dataMapper = $this->get(DataMapper::class);
        $mappedObjectsArray = $dataMapper->map(Example::class, $rows);

        self::assertInstanceOf(CustomDateTime::class, $mappedObjectsArray[0]->getCustomDateTime());
    }

    /**
     * @test
     */
    public function getPlainValueReturnsCorrectDateTimeFormat(): void
    {
        $dataMapper = $this->get(DataMapper::class);
        $columnMapDateTime = new ColumnMap('column_name');
        $columnMapDateTime->setDateTimeStorageFormat('datetime');
        $columnMapDate = new ColumnMap('column_name');
        $columnMapDate->setDateTimeStorageFormat('date');
        $input = new \DateTime('2013-04-15 09:30:00');

        $plainValueDateTime = $dataMapper->getPlainValue($input, $columnMapDateTime);
        $plainValueDate = $dataMapper->getPlainValue($input, $columnMapDate);

        self::assertSame('2013-04-15 09:30:00', $plainValueDateTime);
        self::assertSame('2013-04-15', $plainValueDate);
    }

    public static function getPlainValueReturnsExpectedValuesDataProvider(): array
    {
        return [
            'datetime to timestamp' => ['1365866253', new \DateTime('@1365866253')],
            'boolean true to 1' => [1, true],
            'boolean false to 0' => [0, false],
            'NULL is handled as string' => ['NULL', null],
            'string value is returned unchanged' => ['RANDOM string', 'RANDOM string'],
            'array is flattened' => ['a,b,c', ['a', 'b', 'c']],
            'deep array is flattened' => ['a,b,c', [['a', 'b'], 'c']],
            'traversable domain object to identifier' => [1, new TraversableDomainObjectExample()],
            'integer value is returned unchanged' => [1234, 1234],
            'float is converted to string' => ['1234.56', 1234.56],
        ];
    }

    /**
     * @test
     * @dataProvider getPlainValueReturnsExpectedValuesDataProvider
     */
    public function getPlainValueReturnsExpectedValues(string|int $expectedValue, mixed $input): void
    {
        $dataMapper = $this->get(DataMapper::class);

        $plainValue = $dataMapper->getPlainValue($input);

        self::assertSame($expectedValue, $plainValue);
    }

    /**
     * @test
     */
    public function getPlainValueCallsGetRealInstanceOnInputIfInputIsInstanceOfLazyLoadingProxy(): void
    {
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Generic/Mapper/Fixtures/administrators.csv');
        $dataMapper = $this->get(DataMapper::class);
        $rowsBlog = [
            [
                'uid' => 1234,
                'administrator' => 1,
            ],
        ];
        $mappedObjectArray = $dataMapper->map(Blog::class, $rowsBlog);

        $plainValue = $dataMapper->getPlainValue($mappedObjectArray[0]->getAdministrator());

        self::assertSame(1, $plainValue);
    }

    /**
     * @test
     */
    public function fetchRelatedRespectsForeignDefaultSortByTCAConfiguration(): void
    {
        // Arrange
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/posts.csv');
        $this->importCSVDataSet('typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/comments.csv');

        $dataMapper = $this->get(DataMapper::class);

        $post = new Post();
        $post->_setProperty('uid', 1);

        // Act
        $comments = $dataMapper->fetchRelated($post, 'comments', '5', false)->toArray();

        // Assert
        self::assertSame(
            [5, 4, 3, 2, 1], // foreign_default_sortby is set to uid desc, see
            array_map(static fn (Comment $comment): int => $comment->getUid(), $comments)
        );
    }
}
