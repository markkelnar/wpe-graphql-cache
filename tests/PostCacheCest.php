<?php
/**
 * This collection of tests is intended to:
 *  create a post
 *  query the post, maybe multiple times
 *  verify content cache hit count
 *  update the post
 *  query the post and verify the cache hit count
 *  purge the cache
 *  query the post and verify the cache hit count
 * 
 */
class PostCacheCest
{
	// The post id created during tests
	public $post_id_1;
	public $post_id_2;

	// Use the following data on example post create/update
	public $post_title = "Cache Post Runner";
	public $post_content_0 = "initial post content";
	public $post_content_1 = "secondary post content";

	public function SetupAtTheBeginning( AcceptanceTester $I )
	{
		$I->setUp();
	}

	/**
	 * While creating a post used for these other tests
	*/
	public function CreatePost( AcceptanceTester $I )
	{
		$post['title'] = $this->post_title;
		$post['content'] = $this->post_content_0;
		$this->post_id_1 = $I->havePost( $post );

		$post['title'] = $this->post_title . ' 2';
		$post['content'] = $this->post_content_0 . ' 2';
		$this->post_id_2 = $I->havePost( $post );
	}

	/**
	 * Query the specific post and check the cache hit counter.
	 * Use this separate step function because the 'wpe_auth' cookie was present in $I tester and bypasses cache.
	 */
	public function VerifySeeCacheHeaderTest( AcceptanceTester $I )
	{
		$I->seePostById( $this->post_id_1 );
		$response = $I->grabDataByPost();
		$I->assertEquals( $this->post_title, $response['title'] );
		$I->assertEquals( "<p>{$this->post_content_0}</p>\n", $response['content'] );
		$I->seeHttpHeader('X-Cache', 'MISS');

		// Query again and see the cached version
		$I->seePostById( $this->post_id_1 );
		$response = $I->grabDataByPost();
		$I->assertEquals( $this->post_title, $response['title'] );
		$I->assertEquals( "<p>{$this->post_content_0}</p>\n", $response['content'] );
		$I->seeHttpHeader('X-Cache', 'HIT: 1');

		$I->seePostById( $this->post_id_2 );
		$response = $I->grabDataByPost();
		$I->assertEquals( $this->post_title . ' 2', $response['title'] );
		$I->assertEquals( "<p>{$this->post_content_0} 2</p>\n", $response['content'] );
		$I->seeHttpHeader('X-Cache', 'MISS');

		// Query again and see the cached version
		$I->seePostById( $this->post_id_2 );
		$response = $I->grabDataByPost();
		$I->assertEquals( $this->post_title . ' 2', $response['title'] );
		$I->assertEquals( "<p>{$this->post_content_0} 2</p>\n", $response['content'] );
		$I->seeHttpHeader('X-Cache', 'HIT: 1');
	}

	/**
	 * I want to change the specific post content to initiate a cache purge
	 */
	public function UpdateThePostTest( AcceptanceTester $I )
	{
		$params['content'] = $this->post_content_1;
		$I->updatePost( $this->post_id_1, $params );
	}

	/**
	 * After changing the post content, verify cache miss
	 */
	public function CheckForCacheMissTest( AcceptanceTester $I )
	{
		$I->seePostById( $this->post_id_1 );
		$I->seeHttpHeader('X-Cache', 'MISS');
		$response = $I->grabDataByPost();
		$I->assertEquals( $this->post_title, $response['title'] );
		$I->assertEquals( "<p>{$this->post_content_1}</p>\n", $response['content'] );

		// Query second post and see the cached version
		$I->seePostById( $this->post_id_2 );
		$response = $I->grabDataByPost();
		$I->assertEquals( $this->post_title . ' 2', $response['title'] );
		$I->assertEquals( "<p>{$this->post_content_0} 2</p>\n", $response['content'] );
		$I->seeHttpHeader('X-Cache', 'HIT: 2');
	}

	public function CleanUpAtTheEnd( AcceptanceTester $I )
	{
		$I->cleanUp();
	}
}