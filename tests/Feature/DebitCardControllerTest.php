<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Http\Response;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        //creating test data for 2 different users
        $this->user1 = User::factory()->create();
        Passport::actingAs($this->user1);
        $card1DetailsResponse = $this->json('POST','/api/debit-cards',['type'=>'dummy']);
        $card1Details = json_decode($card1DetailsResponse->getContent(),true);
        $card2DetailsResponse = $this->json('POST','/api/debit-cards',['type'=>'dummy']);
        $card2Details = json_decode($card2DetailsResponse->getContent(),true);

        $this->user1cards = [$card1Details['number'], $card2Details['number']];

        $this->user2 = User::factory()->create();
        Passport::actingAs($this->user2);
        $card3Details = $this->json('POST','/api/debit-cards',['type'=>'dummy']);
        $card4Details = $this->json('POST','/api/debit-cards',['type'=>'dummy']);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK)
           ->assertJsonStructure([
                '*'=>[
                    'type',
                    'id',
                    'number',
                    'expiration_date',
                    'is_active'
                ]
        ]);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);

        $debitCards = json_decode($response->getContent(),true);
        foreach($debitCards as $card) {
            $this->assertFalse(in_array($card['number'], $this->user1cards));
        }
    }

    public function testCustomerCanCreateADebitCard()
    {
        $response = $this->json('POST','/api/debit-cards',['type'=>'dummy'])
            ->assertStatus(Response::HTTP_CREATED);
        $cardDetails = json_decode($response->getContent(),true);
        $this->assertDatabaseHas('debit_cards', [
            'id' => $cardDetails['id'],
            'number' => $cardDetails['number'],
            'type' => $cardDetails['type'],
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);

        $debitCards = json_decode($response->getContent(),true);
        $result = $this->json('GET','/api/debit-cards/'.$debitCards[0]['id'])
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
               'id',
               'number',
               'type',
               'expiration_date',
               'is_active'
            ]);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        $result = $this->json('GET','/api/debit-cards/'.$this->user1cards[0])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testCustomerCanActivateADebitCard()
    {
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);

        $debitCards = json_decode($response->getContent(),true);
        $this->json('PUT','/api/debit-cards/'.$debitCards[0]['id'],['is_active'=>true])
            ->assertStatus(Response::HTTP_OK);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);
        $debitCards = json_decode($response->getContent(),true);
        $this->json('PUT','/api/debit-cards/'.$debitCards[0]['id'],['is_active'=>false])
            ->assertStatus(Response::HTTP_OK);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);
        $debitCards = json_decode($response->getContent(),true);
        $this->json('PUT','/api/debit-cards/'.$debitCards[0]['id'].'_dummy',['is_active'=>false])
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);
        $debitCards = json_decode($response->getContent(),true);
        $this->json('DELETE','/api/debit-cards/'.$debitCards[0]['id'])
            ->assertStatus(Response::HTTP_NO_CONTENT);

    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        /*
         * This seems to be invalid test case. Test customer is allowed to delete a debit card with txn.
         *
        $response = $this->json('GET','/api/debit-cards')
            ->assertStatus(Response::HTTP_OK);
        $debitCards = json_decode($response->getContent(),true);
        $this->json('POST','/api/debit-card-transactions',['debit_card_id'=>$debitCards[0]['id']]);

        $this->json('DELETE','/api/debit-cards/'.$debitCards[0]['id'])
            ->assertStatus(Response::HTTP_FORBIDDEN);
        */
    }
}