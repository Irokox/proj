<?php

namespace App\Http\Livewire;

use App\Models\Post;
use Livewire\Component;
use App\Models\Rating;


class PostShow extends Component
{
    public $post;


    public function mount($slug)
    {
        $this->post = Post::where('slug', $slug)->first();
    }
    public function show($id)
    {
        $post = \App\Models\Post::findOrFail($id);
        return view('post-show', compact('post'));
    }
    public function render()
    {
        $avgs=Rating::where('product_id', $this->post->id)->avg('srating');

        return view('livewire.post-show',[
            'avgs'=>$avgs,
        ])->layout('layouts.guest');
    }
}
