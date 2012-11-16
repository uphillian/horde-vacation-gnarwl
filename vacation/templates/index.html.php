<form method="post" action="<?php echo $this->h($this->url) ?>" name="vacation" id="vacation">
<?php echo $this->formInput ?>

<h1 class="header"><?php echo $this->header ?></h1>

<table class="striped" style="border-collapse: collapse; width: 100%;">

<tr>
 <td width="15%" class="rightAlign">
  <strong><?php echo $this->label->subject ?></strong>
 </td>
 <td>
  <input type="vacation" id="subject" name="subject" size="75" />
 </td>
 <td class="rightAlign">
  <?php echo $this->help->subject ?>
 </td>
</tr>

<tr>
 <td class="rightAlign">
  <strong><?php echo $this->label->message ?></strong>
 </td>
 <td>
  <textarea type="vacation" id="message" name="message" class="fixed" rows="20" cols="75"></textarea>
 </td>
 <td class="rightAlign">
  <?php echo $this->help->message ?>
 </td>
</tr>

<tr>
 <td class="rightAlign">
  <strong><?php echo $this->label->howoften ?></strong>
 </td>
 <td style="direction: ltr">
  <select id="howoften" name="howoften">
   <?php foreach ($this->howoften_options as $key => $description): ?>
   <option value="<?php echo $key ?>"><?php echo $description ?></option>
   <?php endforeach ?>
  </select>
 </td>
 <td class="rightAlign">
     <?php echo $this->help->howoften ?>
 </td>
</tr>


<tr class="control">
 <td colspan="3" class="control">
  <input class="button" type="submit" name="submit" id="submit" value="<?php echo _("Set vacation message") ?>" />
  <input class="button" type="unset" name="unset" value="<?php echo _("Disable vacation message") ?>" />
 </td>
</tr>
</table>
</form>
