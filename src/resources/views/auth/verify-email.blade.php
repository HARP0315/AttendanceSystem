<div class="verify-email">
        <div class="verify-email__inner">
            <p class="verify-email__inner-message">
                ご登録いただいたメールアドレスに認証メールを送信しました。<br>
                メール認証を完了してください。
            </p>
            {{-- 認証メール再送セッションメッセージ --}}
            @if (session('status') == 'verification-link-sent')
                <p class="verify-email__success-message">
                    新しい認証メールを送信しました。
                </p>
            @endif
            <div class="verify-email__inner-actions">
                <div class="verify-email__inner-link-wrapper">
                    <a
                     href="http://localhost:8025"
                     target="_blank"
                     class="verify-email__inner-verify-button"
                    >
                     認証はこちらから
                    </a>
                </div>
                <form
                 class="verify-email__inner-resend-form"
                 method="POST"
                 action="{{ route('verification.send') }}"
                >
                    @csrf
                    <input
                     type="submit"
                     class="verify-email__inner-resend-link"
                     value="認証メールを再送する"
                    >
                </form>
            </div>
        </div>
    </div>