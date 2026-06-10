<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Article;

class ArticlePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Article $article): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Article $article): bool
    {
        return $user->id === $article->user_id || $user->role->value === 'admin';
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->id === $article->user_id || $user->role->value === 'admin';
    }
}
