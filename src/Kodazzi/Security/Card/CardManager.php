<?php
 /**
 * This file is part of the Yulois Framework.
 *
 * (c) Jorge Gaitan <info.yulois@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kodazzi\Security\Card;

use Kodazzi\Session\SessionBuilder;
use Kodazzi\Security\Card\GenericCard;
use Kodazzi\Security\Card\CardInterface;

class CardManager
{
    private $Session = null;

    public function __construct(SessionBuilder $session)
    {
        $this->Session = $session;
    }

    /**
     * @return CardInterface
     */
    public function getCard()
    {
        if($this->Session->has('user_card'))
        {
            $user_card = $this->Session->get('user_card');

            if(is_string($user_card))
            {
                return unserialize($user_card);
            }
        }

        return null;
    }

    /**
     * @return CardInterface
     */
    public function getNewCard()
    {
        return \Service::get('generic_user_card');
    }

    public function add(CardInterface $card)
    {
        $this->Session->set('user_card', serialize($card));
    }

    public function clear()
    {
        $this->Session->remove('user_card');
    }
}