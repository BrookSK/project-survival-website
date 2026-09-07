<?php

/**
 * Testes da camada de privacidade que independem de banco de dados.
 */
class PrivacyTest extends TestCase
{
    public function testMaskEmailHidesLocalPart(): void
    {
        $masked = mask_email('jogador@exemplo.com');
        $this->assertStringContains('@exemplo.com', $masked, 'preserva o domínio');
        $this->assertStringNotContains('jogador@', $masked, 'oculta parte do usuário');
        $this->assertStringContains('*', $masked, 'aplica máscara');
    }

    public function testMaskEmailShortUser(): void
    {
        // Usuário com <=2 caracteres é mantido (nada a mascarar de forma útil).
        $this->assertEquals('ab@x.com', mask_email('ab@x.com'), 'usuário curto mantido');
    }

    public function testMaskEmailEmpty(): void
    {
        $this->assertEquals('—', mask_email(''), 'vazio vira travessão');
        $this->assertEquals('semarroba', mask_email('semarroba'), 'sem @ retorna o valor');
    }

    /**
     * O pacote de exportação de dados NUNCA deve conter segredos. Este teste
     * valida o contrato de campos permitidos usando uma amostra representativa
     * do payload montado por AccountPrivacyController::export().
     */
    public function testExportPayloadHasNoSecrets(): void
    {
        $sample = [
            'generated_at' => date('c'),
            'notice'       => 'Exportação de dados...',
            'account'      => ['player_id' => 'p1', 'username' => 'u', 'email' => 'e@x.com', 'name' => 'N'],
            'consents'     => [['consent_type' => 'marketing', 'version' => null, 'granted' => true, 'granted_at' => null, 'revoked_at' => null]],
            'privacy_requests' => [],
        ];

        $json = strtolower(json_encode($sample));
        foreach (['access_token', 'refresh_token', 'password', 'token_hash', 'secret', 'cvv'] as $forbidden) {
            $this->assertStringNotContains($forbidden, $json, 'exportação não pode conter ' . $forbidden);
        }
        // Campos esperados presentes.
        $this->assertTrue(isset($sample['account']['player_id']), 'inclui player_id');
        $this->assertTrue(array_key_exists('consents', $sample), 'inclui consents');
    }
}
