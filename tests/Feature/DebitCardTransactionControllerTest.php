<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;
    protected DebitCard $debitCard1;
    protected DebitCard $debitCard2;

    protected function setUp(): void
    {
        parent::setUp();

        //Creating Test data
        $this->user1 = User::factory()->create();
        Passport::actingAs($this->user1);
        $this->debitCard1 = DebitCard::factory()->create([
            'user_id' => $this->user1->id
        ]);
        $this->debitCard1Details = json_decode($this->debitCard1,true);

        $this->user2 = User::factory()->create();
        Passport::actingAs($this->user2);
        $this->debitCard2 = DebitCard::factory()->create([
            'user_id' => $this->user2->id
        ]);
        $this->debitCard2Details = json_decode($this->debitCard2,true);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        $this->json('GET','/api/debit-card-transactions?debit_card_id='.$this->debitCard2Details['id'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                '*'=>[
                    'amount',
                    'number',
                    'debit_card_id',
                    'id'
                ]
            ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        $this->json('GET','/api/debit-card-transactions?debit_card_id='.$this->debitCard1Details['id'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        $response = $this->json('POST','/api/debit-card-transactions',['debit_card_id'=>$this->debitCard2Details['id'],'amount'=>'500','currency_code'=>'SGD'])->assertStatus(Response::HTTP_CREATED);;
        $txnDetails = json_decode($response->getContent(),true);
        $this->assertDatabaseHas('debit_card_transactions', [
            'amount' => $txnDetails['amount'],
            'currency_code' => $txnDetails['currency_code'],
            'debit_card_id' => $this->debitCard2Details['id']
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        $this->json('POST','/api/debit-card-transactions',['debit_card_id'=>$this->debitCard1Details['id'],'amount'=>'500','currency_code'=>'SGD'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        $this->json('GET','/api/debit-card-transactions?debit_card_id='.$this->debitCard2Details['id'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                '*'=>[
                    'amount',
                    'currency_code',
                ]
            ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        $this->json('GET','/api/debit-card-transactions?debit_card_id='.$this->debitCard1Details['id'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
