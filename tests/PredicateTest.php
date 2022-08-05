<?php

use PHPUnit\Framework\TestCase;

use Evolv\Predicate;

class PredicateTest extends TestCase
{
    protected Predicate $predicate;

    public function setUp(): void
    {
        $this->predicate = new Predicate();
    }

    /**
     * @test
     */
    public function shouldEvaluateFlatPredicateCorrectly()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.referrer',
                    'operator' => 'exists',
                    'value' => null,
                    'index' => 0
                ],
                1 => [
                    'field' => 'platform',
                    'operator' => 'equal',
                    'value' => 'ios',
                    'index' => 1
                ]
            ]
        ];
        $context = [
            'web' => [
                'referrer' => 'http://stackoverflow.com/'
            ],
            'platform' => 'ios'
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(2, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function shouldEvaluateARealMobileDevicePredicateCorrectly()
    {
        // Arrange
        $predicate = [
            'rules' => [
                0 => [
                    'operator' => 'equal',
                    'field' => 'device',
                    'id' => 'r-ce38958d-1f13-4761-8e07-f87979db3903',
                    'value' => 'mobile',
                    'index' => 1
                ]
            ],
            'combinator' => 'and',
            'id' => 382
        ];
        $context = [
            'web' => [
                'referrer' => 'http://vince-repo.digitalcertainty.net/fb/'
            ],
            'ip_address' => '64.71.166.242',
            'device' => 'mobile'
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldSelectAUserIntoAnExperimentWithAQueryStringFilterCorrectly()
    {
        // Arrange
        $predicate = [
            'rules' => [
                0 => [
                    'operator' => 'equal',
                    'field' => 'web.query_parameters.testing',
                    'id' => 'r-6b2f1647-4ced-4365-b378-0e109b040897',
                    'value' => 'test1',
                    'index' => 0,
                ],
            ],
            'combinator' => 'and',
            'id' => 'g-4f151a9c-2710-4b61-9b77-040c00b7cdf2',
        ];
        $context = [
            'web' => [
                'user_agent' => 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 ',
                'request_url' => 'https://w.net?utm_campaign=com',
                'referrer' => 'http://stackoverflow.com/',
                'query_parameters' => [
                    'testing' => 'test1',
                ],
            ],
            'device' => 'phone',
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldExcludeAUserThatDoesntMeetTheQueryStringFilter()
    {
        // Arrange
        $predicate = [
            'rules' => [
                0 => [
                    'operator' => 'equal',
                    'field' => 'web.query_parameters.testing',
                    'id' => 'r-6b2f1647-4ced-4365-b378-0e109b040897',
                    'value' => 'test1',
                    'index' => 0,
                ],
            ],
            'combinator' => 'and',
            'id' => 'g-4f151a9c-2710-4b61-9b77-040c00b7cdf2',
        ];
        $context = [
            'web' => [
                'user_agent' => 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 ',
                'url' => 'https://w.net?utm_campaign=com',
                'referrer' => 'http://stackoverflow.com/',
                'query_parameters' => [
                    'testing' => 'test2',
                ],
            ],
            'device' => 'phone',
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(0, $result['passed']);
        $this->assertCount(1, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldFailIfAllClausesOfAnOrFail()
    {
        // Arrange
        $predicate = [
            'id' => 1,
            'combinator' => 'or',
            'rules' => [
                0 => [
                    'field' => 'device',
                    'operator' => 'equal',
                    'value' => 'phone',
                    'index' => 0,
                ],
                1 => [
                    'field' => 'device',
                    'operator' => 'equal',
                    'value' => 'desktop',
                    'index' => 1,
                ],
            ],
        ];
        $context = [
            'web' => [
                'user_agent' => 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 ',
                'url' => 'https://w.net?utm_campaign=com',
                'referrer' => 'http://stackoverflow.com/',
            ],
            'device' => 'tablet',
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(0, $result['passed']);
        $this->assertCount(2, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldFailIfAnyClausesOfAnAndFail()
    {
        // Arrange
        $predicate = [
            'id' => 1,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'device',
                    'operator' => 'equal',
                    'value' => 'tablet',
                    'index' => 0,
                ],
                1 => [
                    'field' => 'device',
                    'operator' => 'equal',
                    'value' => 'desktop',
                    'index' => 1,
                ],
            ],
        ];
        $context = [
            'web' => [
                'user_agent' => 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 ',
                'url' => 'https://w.net?utm_campaign=com',
                'referrer' => 'http://stackoverflow.com/',
            ],
            'device' => 'tablet',
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(1, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldEvaluateARegexPredicateCorrectlyl()
    {
        // Arrange
        $predicate = [
            'combinator' => 'and',
            'id' => 583,
            'rules' => [
                [
                    'field' => 'ip_address',
                    'id' => 'r-ba15bef1-1606-42c5-a60b-96583c06c12f',
                    'index' => 0,
                    'operator' => 'regex64_match',
                    'value' => base64_encode('192.169.0.1')
                ]
            ],
        ];
        $context = [
            'ip_address' => '192.169.0.1'
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
    }

    /**
     * @test
     */
    public function ShouldEvaluateARealPredicateCorrectly()
    {
        // Arrange
        $predicate = [
            'combinator' => 'and',
            'id' => 583,
            'rules' => [
                0 => [
                    'field' => 'ip_address',
                    'id' => 'r-ba15bef1-1606-42c5-a60b-96583c06c12f',
                    'index' => 0,
                    'operator' => 'equal',
                    'value' => '64.71.166.242',
                ],
                1 => [
                    'combinator' => 'and',
                    'id' => 583,
                    'rules' => [
                        0 => [
                            'field' => 'device',
                            'id' => 'r-92325c89-e07e-4dd4-8e88-15528c19d43c',
                            'index' => 1,
                            'operator' => 'equal',
                            'value' => 'mobile',
                        ],
                        1 => [
                            'combinator' => 'and',
                            'id' => 583,
                            'rules' => [
                                0 => [
                                    'field' => 'web.referrer',
                                    'id' => 'r-db644b3f-d56e-478f-984f-4c5a1099cd35',
                                    'index' => 2,
                                    'operator' => 'equal',
                                    'value' => 'http://vince-repo.digitalcertainty.net/test/',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $context = [
            'web' => [
                'url' => 'http://vince-repo.digitalcertainty.net/fb/',
            ],
            'ip_address' => '64.71.166.242',
            'device' => 'mobile',
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(2, $result['passed']);
        $this->assertCount(1, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldNotFilterEnergidirectUserWithNotContains()
    {
        // Arrange
        $predicate = [
            'id' => 'g-3909d043-f42b-45ba-8f28-17b1da5307e0',
            'rules' => [
                0 => [
                    'operator' => 'not_contains',
                    'id' => 'r-f2dc0b86-665f-41d7-a142-7dd0658fa9bd',
                    'field' => 'web.query_parameters.ecmp',
                    'value' => [
                        0 => 'aff:dav',
                    ],
                    'index' => 1,
                ],
            ],
            'combinator' => 'or',
        ];
        $context = [
            'web' => [
                'query_parameters' => [
                    'ecmp' => 'sea:nbs:acq:google::nonbrand-stroom::con',
                    'gclsrc' => 'aw.ds',
                    'campaignid' => 'sea:43700014200286505',
                    'gclid' => '*',
                ],
            ],
            'action' => 'get_candidate',
            'cid' => '',
            'sid' => '2041944482_1558310400',
            'did' => '1540762943_1558310400',
            'uid' => '3592933167_1558310400',
            'ver' => '3',
            'page' => '/beste-bod?ecmp=sea:nbs:acq:google::nonbrand-stroom::con&gclsrc=aw.ds&campaignid=sea:43700014200286505&gclid=*',
            'rtver' => '3.1.568',
            'acode' => '263109707-2',
            'filters' => 'JwnEl_00_1',
            'user_attributes' => [
            ],
            'country' => 'US',
            'platform' => 'other',
            'browser' => 'safari',
            'device' => 'desktop',
            'agent' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Safari/537.36',
            'experiments' => [
            ],
            'ipFilter' => false,
            'bot' => true,
            'ip_address' => '66.249.69.101',
            'message_version' => 1,
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldFilterEnergidirectUserWithNotContains()
    {
        // Arrange
        $predicate = [
            'id' => 'g-3909d043-f42b-45ba-8f28-17b1da5307e0',
            'rules' => [
                0 => [
                    'operator' => 'not_contains',
                    'id' => 'r-f2dc0b86-665f-41d7-a142-7dd0658fa9bd',
                    'field' => 'web.query_parameters.ecmp',
                    'value' => ['aff'=>'dav'],
                    'index' => 1,
                ],
            ],
            'combinator' => 'or',
        ];
        $context = [
            'web' => [
                'query_parameters' => [
                    'ecmp' => 'sea:nbs:acq:google:aff:dav:nonbrand-stroom::con',
                    'gclsrc' => 'aw.ds',
                    'campaignid' => 'sea:43700014200286505',
                    'gclid' => '*',
                ],
            ],
            'action' => 'get_candidate',
            'cid' => '',
            'sid' => '2041944482_1558310400',
            'did' => '1540762943_1558310400',
            'uid' => '3592933167_1558310400',
            'ver' => '3',
            'page' => '/beste-bod?ecmp=sea:nbs:acq:google::nonbrand-stroom::con&gclsrc=aw.ds&campaignid=sea:43700014200286505&gclid=*',
            'rtver' => '3.1.568',
            'acode' => '263109707-2',
            'country' => 'US',
            'platform' => 'other',
            'browser' => 'safari',
            'device' => 'desktop',
            'agent' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Safari/537.36',
            'experiments' => [
            ],
            'ipFilter' => false,
            'bot' => true,
            'ip_address' => '66.249.69.101',
            'message_version' => 1,
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }


    /**
     * @test
     */
    public function ShouldEvaluateAFlatPredicateCorrectly()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.referrer',
                    'operator' => 'exists',
                    'value' => '',
                    'index' => 0,
                ],
                1 => [
                    'field' => 'platform',
                    'operator' => 'equal',
                    'value' => 'ios',
                    'index' => 1,
                ],
            ],
        ];
        $context = [
            'web' => [
                'referrer' => 'localhost',
            ],
            'platform' => 'ios',
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(2, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldEvaluateGreaterThanAndLessThanProperties()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.pageWidth',
                    'operator' => 'greater_than',
                    'value' => 1200,
                    'index' => 0,
                ],
                1 => [
                    'field' => 'web.pageWidth',
                    'operator' => 'less_than',
                    'value' => 1400,
                    'index' => 0,
                ],
            ],
        ];
        $context = [
            'web' => [
                'pageWidth' => 1300,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(2, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldEvaluateGreaterThanOrEqualToAndLessThanOrEqualToProperties()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.pageWidth',
                    'operator' => 'greater_than_or_equal_to',
                    'value' => 1200,
                    'index' => 0,
                ],
                1 => [
                    'field' => 'web.pageWidth',
                    'operator' => 'less_than_or_equal_to',
                    'value' => 1500,
                    'index' => 0,
                ],
            ],
        ];
        $context = [
            'web' => [
                'pageWidth' => 1200,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(2, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldEvaluateDoesNotExistProperty()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'platform',
                    'operator' => 'not_exists',
                    'index' => 0,
                ],
            ],
        ];
        $context = [
            'web' => [
                'pageWidth' => 1200,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
/*    public function ShouldEvaluateIsTrueProperty()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.isDesktop',
                    'operator' => 'is_true',
                    'index' => 0,
                ],
            ],
        ];
        $context = [
            'web' => [
                'isDesktop' => true,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }*/

        /**
         * @test
         */
/*    public function ShouldEvaluateIsFalseProperty()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.isDesktop',
                    'operator' => 'is_false',
                ],
            ],
        ];
        $context = [
            'web' => [
                'isDesktop' => false,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }*/

    /**
     * @test
     */
    public function ShouldEvaluateTypelessEqualProperty()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.pageWidth',
                    'operator' => 'loose_equal',
                    'value' => 1200,
                    'index' => 0,
                ],
            ],
        ];
        $context = [
            'web' => [
                'pageWidth' => 1200,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

    /**
     * @test
     */
    public function ShouldEvaluateTypelessNotEqualProperty()
    {
        // Arrange
        $predicate = [
            'id' => 123,
            'combinator' => 'and',
            'rules' => [
                0 => [
                    'field' => 'web.pageWidth',
                    'operator' => 'loose_not_equal',
                    'value' => 1250,
                    'index' => 0,
                ],
            ],
        ];
        $context = [
            'web' => [
                'pageWidth' => 1200,
            ],
        ];

        // Act
        $result = $this->predicate->evaluate($context, $predicate);

        // Assert
        $this->assertCount(1, $result['passed']);
        $this->assertCount(0, $result['failed']);
    }

}
