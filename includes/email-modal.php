<div id="email-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-actions">
                <button class="modal-action-btn reply-btn" onclick="redirectToReply(this)" title="Reply">
                    <i class="fas fa-reply"></i> Reply
                </button>
                <button class="modal-action-btn" title="Forward">
                    <i class="fas fa-forward"></i>
                </button>
                <button class="modal-action-btn" title="Archive">
                    <i class="fas fa-archive"></i>
                </button>
                <button class="modal-action-btn delete-btn" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <button class="close-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <!-- Content will be inserted here by JavaScript -->
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: white;
    margin: 50px auto;
    width: 90%;
    max-width: 800px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    max-height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8f9ff;
    border-bottom: 1px solid #eef0ff;
    border-radius: 12px 12px 0 0;
}

.modal-actions {
    display: flex;
    gap: 10px;
}

.modal-action-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border: none;
    background: none;
    color: #666;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
}

.modal-action-btn:hover {
    background: #eef0ff;
    color: #5e64ff;
}

.close-modal {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.close-modal:hover {
    background: #eef0ff;
    color: #5e64ff;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.delete-btn:hover {
    background: #fff5f5;
    color: #ff4757;
}

@media (max-width: 768px) {
    .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
}
</style> 