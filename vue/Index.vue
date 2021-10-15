<template>
    <v-flex
        md12
        class="px-12 pb-12"
    >
        <PageHeader
            :breadcrumbs="breadcrumbs"
            :title="$t('calendar.overview')"
        />
        <v-col cols="12">
            <v-row dense>
                <v-col
                    cols="12"
                >
                    <v-progress-linear
                        v-if="loading"
                        indeterminate
                        color="primary"
                    />
                </v-col>
                <v-col cols="2">
                    <v-skeleton-loader
                        v-if="loading"
                        class="mx-auto"
                        type="table-tbody"
                    />
                </v-col>
                <v-col cols="10">
                    <v-skeleton-loader
                        v-if="loading"
                        class="mx-auto"
                        type="table-tbody"
                    />
                </v-col>
            </v-row>
            <CalendarOverview
                v-if="!loading"
                :objects="objects"
                :search="textSearch"
            />
        </v-col>
    </v-flex>
</template>

<script>

import PageHeader from '@/components/common/PageHeader';
import CalendarOverview from '@/components/calendarOverview/OverviewList';
import CourseCalendarProxy from '@/proxies/CourseCalendarProxy';

import { SuccessSnackbar, ObjectDeleted } from '@/app-events.js';

export default {
    name: 'CourseIndex',
    components: {
        PageHeader,
        CalendarOverview,
    },
    data() {
        return {
            breadcrumbs: [
                {
                    text: this.$t('page.home.title'),
                    exact: true,
                    to: { name: 'home' },
                },
                {
                    text: this.$t('calendar.overview'),
                    disabled: true,
                },
            ],
            textSearch: '',
            objects: [],
            loading: true,
        };
    },
    created () {
        this.fetchData();
    },
    methods: {
        fetchData() {
            this.loading = true;
            new CourseCalendarProxy().all().then(({ data }) => {
                this.objects = data.data;
                this.loading = false;
            }).catch(({ data }) => {
                this.displayError(data);
            });
        },
        searchInputListener(value) {
            this.textSearch = value;
        },
    },
};
</script>
