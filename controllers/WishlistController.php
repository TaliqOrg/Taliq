<?php

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../models/Wishlist.php';
require_once '../includes/functions.php';

class WishlistController {
    private $wishlistModel;

    public function __construct() {
        $this->wishlistModel = new Wishlist();
    }

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

    public function getItems($userId) {
        $items = $this->wishlistModel->getAll($userId);
        return [
            'success' => true,
            'items' => $items,
            'count' => count($items)
        ];
    }

    public function getCount($userId) {
        return $this->wishlistModel->getCount($userId);
    }

    public function getWishlistIds($userId) {
        return $this->wishlistModel->getWishlistIds($userId);
    }

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

    public function clear($userId) {
        $this->wishlistModel->clear($userId);
        return [
            'success' => true,
            'message' => 'Wishlist cleared',
            'count' => 0
        ];
    }
}
