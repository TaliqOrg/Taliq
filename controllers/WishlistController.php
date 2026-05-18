<?php
/**
 * Wishlist Controller
 *
 * Manages wishlist operations for authenticated users. Provides functionality
 * to toggle items in/out of the wishlist, add items, remove items, clear the
 * entire wishlist, retrieve all wishlist items, get item count, fetch wishlist
 * IDs, and check if a specific item exists in the wishlist.
 *
 * @package    Taliq\Controllers
 * @subpackage Wishlist
 * @version    1.0.0
 */

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Wishlist.php';
require_once '../includes/functions.php';

class WishlistController {
    private $wishlistModel;

    public function __construct() {
        $this->wishlistModel = new Wishlist();
    }

    /**
     * Toggles an item in the user's wishlist (adds if absent, removes if present).
     *
     * @param int      $userId     The authenticated user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return array Associative array with success status, action taken, message, and count.
     */
    public function toggle($userId, $courseId, $workshopId) {
        if (!$courseId && !$workshopId) {
            return ['success' => false, 'message' => 'No item specified'];
        }

        $isInWishlist = $this->wishlistModel->isInWishlist($userId, $courseId, $workshopId);

        if ($isInWishlist) {
            $this->wishlistModel->remove($userId, $courseId, $workshopId);
            return [
                'success' => true,
                'action' => 'removed',
                'message' => 'Removed from wishlist',
                'count' => $this->wishlistModel->getCount($userId)
            ];
        } else {
            $this->wishlistModel->add($userId, $courseId, $workshopId);
            return [
                'success' => true,
                'action' => 'added',
                'message' => 'Added to wishlist',
                'count' => $this->wishlistModel->getCount($userId)
            ];
        }
    }

    /**
     * Adds an item to the user's wishlist if not already present.
     *
     * @param int      $userId     The authenticated user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return array Associative array with success status, message, and count.
     */
    public function add($userId, $courseId, $workshopId) {
        if (!$courseId && !$workshopId) {
            return ['success' => false, 'message' => 'No item specified'];
        }

        if ($this->wishlistModel->isInWishlist($userId, $courseId, $workshopId)) {
            return [
                'success' => true,
                'message' => 'Already in wishlist',
                'count' => $this->wishlistModel->getCount($userId)
            ];
        }

        $this->wishlistModel->add($userId, $courseId, $workshopId);
        return [
            'success' => true,
            'message' => 'Added to wishlist!',
            'count' => $this->wishlistModel->getCount($userId)
        ];
    }

    /**
     * Removes an item from the user's wishlist.
     *
     * @param int      $userId     The authenticated user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return array Associative array with success status, message, and count.
     */
    public function remove($userId, $courseId, $workshopId) {
        if (!$courseId && !$workshopId) {
            return ['success' => false, 'message' => 'No item specified'];
        }

        $this->wishlistModel->remove($userId, $courseId, $workshopId);
        return [
            'success' => true,
            'message' => 'Removed from wishlist',
            'count' => $this->wishlistModel->getCount($userId)
        ];
    }

    /**
     * Retrieves all items in the user's wishlist.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, items, and count.
     */
    public function getItems($userId) {
        $items = $this->wishlistModel->getAll($userId);
        return [
            'success' => true,
            'items' => $items,
            'count' => count($items)
        ];
    }

    /**
     * Returns the total number of items in the user's wishlist.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return int The wishlist item count.
     */
    public function getCount($userId) {
        return $this->wishlistModel->getCount($userId);
    }

    /**
     * Returns all wishlist item IDs for the user.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Array of wishlist item identifiers.
     */
    public function getWishlistIds($userId) {
        return $this->wishlistModel->getWishlistIds($userId);
    }

    /**
     * Checks if a specific item is in the user's wishlist.
     *
     * @param int      $userId     The authenticated user's ID.
     * @param int|null $courseId   The course ID, or null.
     * @param int|null $workshopId The workshop ID, or null.
     *
     * @return array Associative array with success status and inWishlist boolean.
     */
    public function check($userId, $courseId, $workshopId) {
        if (!$courseId && !$workshopId) {
            return ['success' => false, 'inWishlist' => false];
        }

        $inWishlist = $this->wishlistModel->isInWishlist($userId, $courseId, $workshopId);
        return [
            'success' => true,
            'inWishlist' => $inWishlist
        ];
    }

    /**
     * Clears all items from the user's wishlist.
     *
     * @param int $userId The authenticated user's ID.
     *
     * @return array Associative array with success status, message, and zero count.
     */
    public function clear($userId) {
        $this->wishlistModel->clear($userId);
        return [
            'success' => true,
            'message' => 'Wishlist cleared',
            'count' => 0
        ];
    }
}
