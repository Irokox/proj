<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Rating;
use Sentiment\Analyzer;

class ProductRatings extends Component
{
    public $rating;
    public $comment;
    public $currentId;
    public $post;
    public $hideForm;
    public $srating='';
    public $review;
    public $avgs;
    public $mood;


    protected $rules = [
        'rating' => ['required', 'in:1,2,3,4,5'],
        'comment' => 'required',

    ];

    public function render()
    {
        
        $comments =     Rating::where('product_id', $this->post->id)->where('status', 1)->with('user')->get();
        return view('livewire.product-ratings', compact('comments'));
    }

    public function mount()
    {
       $avgs=Rating::where('product_id', $this->post->id)->avg('srating');

        if(auth()->user()){
        
           
            $rating = Rating::where('user_id', auth()->user()->id)->where('product_id', $this->post->id)->first();
            if (!empty($rating)) {
                $this->rating  = $rating->rating;
                $this->comment = $rating->comment;
                $this->currentId = $rating->id;
            }
        }
        return view('livewire.product-ratings', [
			'avgs'=>$avgs,
        ]);
    }

    public function delete($id)
    {
        $rating = Rating::where('id', $id)->first();
        if ($rating && ($rating->user_id == auth()->user()->id)) {
            $rating->delete();
        }
        if ($this->currentId) {
            $this->currentId = '';
            $this->rating  = '';
            $this->comment = '';
        }
    }

    public function rate()
    {
        $rating = Rating::where('user_id', auth()->user()->id)->where('product_id', $this->post->id)->first();
        
        $this->validate();
        $analyzer = new Analyzer();
        $output_text = $analyzer->getSentiment($this->comment);
        $this->srating=$output_text['compound'];
        $this->srating=($this->srating+1)*2.5;
        $mood        = '';

        if($output_text['neg'] > 0 && $output_text['neg'] < 0.49){
            $this->mood = 'Somewhat Negative ';
        }
        elseif($output_text['neg'] > 0.49){
            $this->mood = 'Mostly Negative';
        }

        if($output_text['neu'] > 0 && $output_text['neg'] < 0.49){
            $this->mood = 'Somewhat neutral ';
        }
        elseif($output_text['neu'] > 0.49){
            $this->mood = 'Mostly neutral';
        }

        if($output_text['pos'] > 0 && $output_text['pos'] < 0.49){
            $this->mood = 'Somewhat positive ';
        }
        elseif($output_text['pos'] > 0.49){
            $this->mood = 'Mostly positive';
        }
       
        
        if (!empty($rating)) {
            $this->hideForm = true;

            session()->flash('message', 'Already Rated this!');
        } else {
            $rating = new Rating;
            $rating->user_id = auth()->user()->id;
            $rating->product_id = $this->post->id;
            $rating->rating = $this->rating;
            $rating->comment = $this->comment;
            $rating->srating= $this->srating;
            $rating->mood= $this->mood;


            $rating->status = 1;
            try {
                $rating->save();
            } catch (\Throwable $th) {
                throw $th;
            }
            $this->hideForm = true;
        }
        return view('livewire.product-ratings');
    }
}

