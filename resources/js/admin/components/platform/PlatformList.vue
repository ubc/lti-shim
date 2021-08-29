<template>
  <div class=''>
    <table class='plainTable'>
      <thead>
        <tr>
          <th scope="col" class='hidden md:table-cell'>ID</th>
          <th scope="col">Name</th>
          <th scope="col" class='hidden md:table-cell'>ISS</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <template v-for='platform in platforms'>
          <tr class='border-b-0'>
            <th scope="row" rowspan='2' class='hidden md:table-cell'>
              {{ platform.id }}</th>
            <td>{{ platform.name }}</td>
            <td class='hidden md:table-cell'>{{ platform.iss }}</td>
            <td class='flex justify-between gap-2'>
              <router-link class='btnSecondary' tag='button'
                :to="{name: 'editPlatform', params: {platformId: platform.id}}">
                <EditIcon /> Edit
              </router-link>
              <AreYouSureButton
                :css="'btnDanger'"
                :warning="'Delete platform: ' + platform.name + '?'"
                @yes='deletePlatform(platform.id)'>
              <DeleteIcon /> Delete
              </AreYouSureButton>
            </td>
          </tr>
          <tr>
            <td colspan='3' class='pt-0'>
              <PlatformClientAdmin :platformId='platform.id'/>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script>
import DeleteIcon from 'icons/Delete'
import EditIcon from 'icons/Pencil'

import AreYouSureButton from '../util/AreYouSureButton'

import PlatformClientAdmin from
  '@admin/components/platformClient/PlatformClientAdmin'

export default {
  name: "PlatformList",
  components: {
    AreYouSureButton,
    DeleteIcon,
    EditIcon,
    PlatformClientAdmin,
  },
  computed: {
    platforms() { return this.$store.state.platform.items }
  },
  methods: {
    deletePlatform(itemId) {
      this.$store.dispatch('platform/delete', itemId)
    }
  },
  mounted() {
    // this 'if' is more to remind myself that lodash exists and provide
    // convenient helpers for dealing with objects as maps/dicts
    if (_.size(this.platforms) == 0) {
      this.$store.dispatch('platform/getAll')
    }
  }

}
</script>

<style scoped>
</style>
