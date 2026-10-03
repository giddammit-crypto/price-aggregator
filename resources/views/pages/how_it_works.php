<div class="container" style="max-width: 800px;">
  <h1 style="font-size: var(--fs-2xl); font-weight: 800; margin-bottom: var(--sp-4);">Как мы считаем и собираем цены</h1>

  <div style="background: var(--c-surface); border: 1px solid var(--c-line); border-radius: var(--r-md); padding: var(--sp-6); margin-bottom: var(--sp-6);">
    <h2 style="font-size: var(--fs-lg); font-weight: 700; color: var(--c-accent); margin-bottom: var(--sp-2);">1. Прямые каналы и фиды данных</h2>
    <p class="text-muted" style="margin-bottom: var(--sp-4);">
      Мы загружаем предложения напрямую из официальных YML/XML каталогов проверенных интернет-магазинов (Ситилинк, Регард, ОнлайнТрейд, М.Видео) и через партнерские API маркетплейсов. Обновление цен происходит автоматически по расписанию от нескольких раз в сутки до ежечасного для популярных позиций.
    </p>

    <h2 style="font-size: var(--fs-lg); font-weight: 700; color: var(--c-accent); margin-bottom: var(--sp-2);">2. Итоговая цена с доставкой (Landed Price)</h2>
    <p class="text-muted" style="margin-bottom: var(--sp-4);">
      Для предложений из-за рубежа (например, площадки AliExpress) цена в рублях рассчитывается по официальному курсу ЦБ РФ на день обновления. Если доставка платная, ее стоимость автоматически прибавляется к цене товара.
    </p>

    <h2 style="font-size: var(--fs-lg); font-weight: 700; color: var(--c-accent); margin-bottom: var(--sp-2);">3. Условные цены и скидки</h2>
    <p class="text-muted" style="margin-bottom: var(--sp-4);">
      Цены с условиями (например, «при оплате картой Ozon» или «по промокоду») маркируются специальным предупреждением и не выдаются за безусловную стоимость товара.
    </p>

    <h2 style="font-size: var(--fs-lg); font-weight: 700; color: var(--c-accent); margin-bottom: var(--sp-2);">4. Режим прямого поиска (Link-Only)</h2>
    <p class="text-muted">
      Для сервисов, где отсутствует официальный открытый фид данных для агрегации цен (например, Wildberries или частные объявления Авито), мы не отображаем вымышленные цены. Вместо этого предоставляется прямая ссылка на поиск товара на соответствующей площадке.
    </p>
  </div>
</div>
