<div class="container" style="max-width: 600px;">
  <h1 style="font-size: var(--fs-2xl); font-weight: 800; margin-bottom: var(--sp-2);">Сообщить о неверной цене</h1>
  <p class="text-muted" style="margin-bottom: var(--sp-4);">Если вы заметили расхождение цены, неверный товар или недобросовестного продавца, сообщите нам.</p>

  <form method="POST" action="/pages/report" style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: var(--sp-6);">
    <?= csrf_field() ?>
    
    <div style="margin-bottom: var(--sp-4);">
      <label class="font-bold" style="display: block; margin-bottom: 6px;">Ключ предложения или URL товара</label>
      <input type="text" name="offer_key" class="search-input" style="border: 1px solid var(--c-line); padding: 0 12px; height: 42px;" placeholder="citilink:12345 или ссылка" required>
    </div>

    <div style="margin-bottom: var(--sp-4);">
      <label class="font-bold" style="display: block; margin-bottom: 6px;">Причина жалобы</label>
      <select name="reason" class="search-input" style="border: 1px solid var(--c-line); padding: 0 12px; height: 42px;" required>
        <option value="price_mismatch">Цена в магазине выше, чем на сайте</option>
        <option value="out_of_stock">Товара нет в наличии</option>
        <option value="wrong_item">Неверная модель или модификация</option>
        <option value="fake_or_spam">Подделка, муляж или аксессуар</option>
        <option value="other">Другое</option>
      </select>
    </div>

    <div style="margin-bottom: var(--sp-6);">
      <label class="font-bold" style="display: block; margin-bottom: 6px;">Комментарий</label>
      <textarea name="comment" rows="4" style="width: 100%; border: 1px solid var(--c-line); border-radius: var(--r-sm); padding: 10px; font-family: inherit; font-size: var(--fs-sm);" placeholder="Опишите подробнее, что не так..."></textarea>
    </div>

    <button type="submit" class="btn btn--accent" style="width: 100%;">Отправить сообщение</button>
  </form>
</div>
