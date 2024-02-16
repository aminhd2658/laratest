<?php

namespace Tests\Feature\Controllers\Admin;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected array $middlewares = ['web', 'admin'];

    public function testIndexMethod(): void
    {
        Post::factory()->count(10)->create();

        $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('post.index'))
            ->assertOk()
            ->assertViewIs('admin.post.index')
            ->assertViewHas('posts', Post::latest()->paginate(15));

        $this->assertEquals(
            $this->middlewares,
            request()->route()->middleware()
        );

    }

    public function testCreateMethod(): void
    {
        Tag::factory()->count(20)->create();

        $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('post.create'))
            ->assertOk()
            ->assertViewIs('admin.post.create')
            ->assertViewHas('tags', Tag::latest()->get());

    }

    public function testEditMethod(): void
    {
        $post = Post::factory()->create();
        Tag::factory()->count(20)->create();

        $this
            ->actingAs(User::factory()->admin()->create())
            ->get(route('post.edit', $post->id))
            ->assertOk()
            ->assertViewIs('admin.post.edit')
            ->assertViewHasAll([
                'post' => $post,
                'tags' => Tag::latest()->get()
            ]);

    }


    public function testStoreMethod(): void
    {
        $user = User::factory()->admin()->create();
        $tags = Tag::factory()->count(rand(1, 5))->create();
        $data = Post::factory()->state(['user_id' => $user->id])->make()->toArray();

        $this
            ->actingAs($user)
            ->post(route('post.store'),
                array_merge(
                    ['tags' => $tags->pluck('id')->toArray()],
                    $data
                )
            )
            ->assertSessionHas('message', 'New post has been created.')
            ->assertRedirect(route('post.index'));

        $this->assertDatabaseHas('posts', $data);

        $this->assertEquals(
            $tags->pluck('id')->toArray(),
            Post::where($data)
                ->first()
                ->tags()
                ->pluck('id')
                ->toArray()
        );

        $this->assertEquals(
            $this->middlewares,
            request()->route()->middleware()
        );
    }

    public function testUpdateMethod(): void
    {
        $user = User::factory()->admin()->create();
        $tags = Tag::factory()->count(rand(1, 5))->create();
        $data = Post::factory()->state(['user_id' => $user->id])->make()->toArray();

        $post = Post::factory()
            ->state(['user_id' => $user->id])
            ->hasTags(rand(1, 5))
            ->create();

        $this
            ->actingAs($user)
            ->patch(route('post.update', $post->id),
                array_merge(
                    ['tags' => $tags->pluck('id')->toArray()],
                    $data
                )
            )
            ->assertSessionHas('message', 'The post has been updated.')
            ->assertRedirect(route('post.index'));

        $this->assertDatabaseHas('posts', array_merge(['id' => $post->id], $data));

        $this->assertEquals(
            $tags->pluck('id')->toArray(),
            Post::where($data)
                ->first()
                ->tags()
                ->pluck('id')
                ->toArray()
        );

        $this->assertEquals(
            $this->middlewares,
            request()->route()->middleware()
        );
    }
}
