<?php
namespace Aws\Connect;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Connect Service** service.
 * @method \Aws\Result activateEvaluationForm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise activateEvaluationFormAsync(array $args = [])
 * @method \Aws\Result associateAnalyticsDataSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateAnalyticsDataSetAsync(array $args = [])
 * @method \Aws\Result associateApprovedOrigin(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateApprovedOriginAsync(array $args = [])
 * @method \Aws\Result associateBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateBotAsync(array $args = [])
 * @method \Aws\Result associateDefaultVocabulary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateDefaultVocabularyAsync(array $args = [])
 * @method \Aws\Result associateFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateFlowAsync(array $args = [])
 * @method \Aws\Result associateInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result associateLambdaFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateLambdaFunctionAsync(array $args = [])
 * @method \Aws\Result associateLexBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateLexBotAsync(array $args = [])
 * @method \Aws\Result associatePhoneNumberContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associatePhoneNumberContactFlowAsync(array $args = [])
 * @method \Aws\Result associateQueueQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateQueueQuickConnectsAsync(array $args = [])
 * @method \Aws\Result associateRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result associateSecurityKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateSecurityKeyAsync(array $args = [])
 * @method \Aws\Result associateTrafficDistributionGroupUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateTrafficDistributionGroupUserAsync(array $args = [])
 * @method \Aws\Result associateUserProficiencies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise associateUserProficienciesAsync(array $args = [])
 * @method \Aws\Result batchAssociateAnalyticsDataSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchAssociateAnalyticsDataSetAsync(array $args = [])
 * @method \Aws\Result batchDisassociateAnalyticsDataSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchDisassociateAnalyticsDataSetAsync(array $args = [])
 * @method \Aws\Result batchGetAttachedFileMetadata(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchGetAttachedFileMetadataAsync(array $args = [])
 * @method \Aws\Result batchGetFlowAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchGetFlowAssociationAsync(array $args = [])
 * @method \Aws\Result batchPutContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchPutContactAsync(array $args = [])
 * @method \Aws\Result claimPhoneNumber(array $args = [])
 * @method \GuzzleHttp\Promise\Promise claimPhoneNumberAsync(array $args = [])
 * @method \Aws\Result completeAttachedFileUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise completeAttachedFileUploadAsync(array $args = [])
 * @method \Aws\Result createAgentStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAgentStatusAsync(array $args = [])
 * @method \Aws\Result createContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createContactAsync(array $args = [])
 * @method \Aws\Result createContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createContactFlowAsync(array $args = [])
 * @method \Aws\Result createContactFlowModule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createContactFlowModuleAsync(array $args = [])
 * @method \Aws\Result createContactFlowVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createContactFlowVersionAsync(array $args = [])
 * @method \Aws\Result createEmailAddress(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEmailAddressAsync(array $args = [])
 * @method \Aws\Result createEvaluationForm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createEvaluationFormAsync(array $args = [])
 * @method \Aws\Result createHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result createInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createInstanceAsync(array $args = [])
 * @method \Aws\Result createIntegrationAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createIntegrationAssociationAsync(array $args = [])
 * @method \Aws\Result createParticipant(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createParticipantAsync(array $args = [])
 * @method \Aws\Result createPersistentContactAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPersistentContactAssociationAsync(array $args = [])
 * @method \Aws\Result createPredefinedAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPredefinedAttributeAsync(array $args = [])
 * @method \Aws\Result createPrompt(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createPromptAsync(array $args = [])
 * @method \Aws\Result createQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createQueueAsync(array $args = [])
 * @method \Aws\Result createQuickConnect(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createQuickConnectAsync(array $args = [])
 * @method \Aws\Result createRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createRoutingProfileAsync(array $args = [])
 * @method \Aws\Result createRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createRuleAsync(array $args = [])
 * @method \Aws\Result createSecurityProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createSecurityProfileAsync(array $args = [])
 * @method \Aws\Result createTaskTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTaskTemplateAsync(array $args = [])
 * @method \Aws\Result createTrafficDistributionGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createTrafficDistributionGroupAsync(array $args = [])
 * @method \Aws\Result createUseCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUseCaseAsync(array $args = [])
 * @method \Aws\Result createUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUserAsync(array $args = [])
 * @method \Aws\Result createUserHierarchyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createUserHierarchyGroupAsync(array $args = [])
 * @method \Aws\Result createView(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createViewAsync(array $args = [])
 * @method \Aws\Result createViewVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createViewVersionAsync(array $args = [])
 * @method \Aws\Result createVocabulary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createVocabularyAsync(array $args = [])
 * @method \Aws\Result deactivateEvaluationForm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deactivateEvaluationFormAsync(array $args = [])
 * @method \Aws\Result deleteAttachedFile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAttachedFileAsync(array $args = [])
 * @method \Aws\Result deleteContactEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteContactEvaluationAsync(array $args = [])
 * @method \Aws\Result deleteContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteContactFlowAsync(array $args = [])
 * @method \Aws\Result deleteContactFlowModule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteContactFlowModuleAsync(array $args = [])
 * @method \Aws\Result deleteEmailAddress(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEmailAddressAsync(array $args = [])
 * @method \Aws\Result deleteEvaluationForm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteEvaluationFormAsync(array $args = [])
 * @method \Aws\Result deleteHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result deleteInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteInstanceAsync(array $args = [])
 * @method \Aws\Result deleteIntegrationAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteIntegrationAssociationAsync(array $args = [])
 * @method \Aws\Result deletePredefinedAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePredefinedAttributeAsync(array $args = [])
 * @method \Aws\Result deletePrompt(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deletePromptAsync(array $args = [])
 * @method \Aws\Result deleteQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteQueueAsync(array $args = [])
 * @method \Aws\Result deleteQuickConnect(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteQuickConnectAsync(array $args = [])
 * @method \Aws\Result deleteRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRoutingProfileAsync(array $args = [])
 * @method \Aws\Result deleteRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteRuleAsync(array $args = [])
 * @method \Aws\Result deleteSecurityProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteSecurityProfileAsync(array $args = [])
 * @method \Aws\Result deleteTaskTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTaskTemplateAsync(array $args = [])
 * @method \Aws\Result deleteTrafficDistributionGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteTrafficDistributionGroupAsync(array $args = [])
 * @method \Aws\Result deleteUseCase(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteUseCaseAsync(array $args = [])
 * @method \Aws\Result deleteUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteUserAsync(array $args = [])
 * @method \Aws\Result deleteUserHierarchyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteUserHierarchyGroupAsync(array $args = [])
 * @method \Aws\Result deleteView(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteViewAsync(array $args = [])
 * @method \Aws\Result deleteViewVersion(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteViewVersionAsync(array $args = [])
 * @method \Aws\Result deleteVocabulary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteVocabularyAsync(array $args = [])
 * @method \Aws\Result describeAgentStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAgentStatusAsync(array $args = [])
 * @method \Aws\Result describeAuthenticationProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeAuthenticationProfileAsync(array $args = [])
 * @method \Aws\Result describeContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeContactAsync(array $args = [])
 * @method \Aws\Result describeContactEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeContactEvaluationAsync(array $args = [])
 * @method \Aws\Result describeContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeContactFlowAsync(array $args = [])
 * @method \Aws\Result describeContactFlowModule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeContactFlowModuleAsync(array $args = [])
 * @method \Aws\Result describeEmailAddress(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEmailAddressAsync(array $args = [])
 * @method \Aws\Result describeEvaluationForm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeEvaluationFormAsync(array $args = [])
 * @method \Aws\Result describeHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result describeInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeInstanceAsync(array $args = [])
 * @method \Aws\Result describeInstanceAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeInstanceAttributeAsync(array $args = [])
 * @method \Aws\Result describeInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result describePhoneNumber(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePhoneNumberAsync(array $args = [])
 * @method \Aws\Result describePredefinedAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePredefinedAttributeAsync(array $args = [])
 * @method \Aws\Result describePrompt(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describePromptAsync(array $args = [])
 * @method \Aws\Result describeQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeQueueAsync(array $args = [])
 * @method \Aws\Result describeQuickConnect(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeQuickConnectAsync(array $args = [])
 * @method \Aws\Result describeRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRoutingProfileAsync(array $args = [])
 * @method \Aws\Result describeRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeRuleAsync(array $args = [])
 * @method \Aws\Result describeSecurityProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeSecurityProfileAsync(array $args = [])
 * @method \Aws\Result describeTrafficDistributionGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeTrafficDistributionGroupAsync(array $args = [])
 * @method \Aws\Result describeUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeUserAsync(array $args = [])
 * @method \Aws\Result describeUserHierarchyGroup(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeUserHierarchyGroupAsync(array $args = [])
 * @method \Aws\Result describeUserHierarchyStructure(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeUserHierarchyStructureAsync(array $args = [])
 * @method \Aws\Result describeView(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeViewAsync(array $args = [])
 * @method \Aws\Result describeVocabulary(array $args = [])
 * @method \GuzzleHttp\Promise\Promise describeVocabularyAsync(array $args = [])
 * @method \Aws\Result disassociateAnalyticsDataSet(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateAnalyticsDataSetAsync(array $args = [])
 * @method \Aws\Result disassociateApprovedOrigin(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateApprovedOriginAsync(array $args = [])
 * @method \Aws\Result disassociateBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateBotAsync(array $args = [])
 * @method \Aws\Result disassociateFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateFlowAsync(array $args = [])
 * @method \Aws\Result disassociateInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result disassociateLambdaFunction(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateLambdaFunctionAsync(array $args = [])
 * @method \Aws\Result disassociateLexBot(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateLexBotAsync(array $args = [])
 * @method \Aws\Result disassociatePhoneNumberContactFlow(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociatePhoneNumberContactFlowAsync(array $args = [])
 * @method \Aws\Result disassociateQueueQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateQueueQuickConnectsAsync(array $args = [])
 * @method \Aws\Result disassociateRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result disassociateSecurityKey(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateSecurityKeyAsync(array $args = [])
 * @method \Aws\Result disassociateTrafficDistributionGroupUser(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateTrafficDistributionGroupUserAsync(array $args = [])
 * @method \Aws\Result disassociateUserProficiencies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise disassociateUserProficienciesAsync(array $args = [])
 * @method \Aws\Result dismissUserContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise dismissUserContactAsync(array $args = [])
 * @method \Aws\Result getAttachedFile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAttachedFileAsync(array $args = [])
 * @method \Aws\Result getContactAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getContactAttributesAsync(array $args = [])
 * @method \Aws\Result getCurrentMetricData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCurrentMetricDataAsync(array $args = [])
 * @method \Aws\Result getCurrentUserData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getCurrentUserDataAsync(array $args = [])
 * @method \Aws\Result getFederationToken(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFederationTokenAsync(array $args = [])
 * @method \Aws\Result getFlowAssociation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getFlowAssociationAsync(array $args = [])
 * @method \Aws\Result getMetricData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getMetricDataAsync(array $args = [])
 * @method \Aws\Result getMetricDataV2(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getMetricDataV2Async(array $args = [])
 * @method \Aws\Result getPromptFile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getPromptFileAsync(array $args = [])
 * @method \Aws\Result getTaskTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTaskTemplateAsync(array $args = [])
 * @method \Aws\Result getTrafficDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getTrafficDistributionAsync(array $args = [])
 * @method \Aws\Result importPhoneNumber(array $args = [])
 * @method \GuzzleHttp\Promise\Promise importPhoneNumberAsync(array $args = [])
 * @method \Aws\Result listAgentStatuses(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAgentStatusesAsync(array $args = [])
 * @method \Aws\Result listAnalyticsDataAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAnalyticsDataAssociationsAsync(array $args = [])
 * @method \Aws\Result listApprovedOrigins(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listApprovedOriginsAsync(array $args = [])
 * @method \Aws\Result listAssociatedContacts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAssociatedContactsAsync(array $args = [])
 * @method \Aws\Result listAuthenticationProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAuthenticationProfilesAsync(array $args = [])
 * @method \Aws\Result listBots(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listBotsAsync(array $args = [])
 * @method \Aws\Result listContactEvaluations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listContactEvaluationsAsync(array $args = [])
 * @method \Aws\Result listContactFlowModules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listContactFlowModulesAsync(array $args = [])
 * @method \Aws\Result listContactFlowVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listContactFlowVersionsAsync(array $args = [])
 * @method \Aws\Result listContactFlows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listContactFlowsAsync(array $args = [])
 * @method \Aws\Result listContactReferences(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listContactReferencesAsync(array $args = [])
 * @method \Aws\Result listDefaultVocabularies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listDefaultVocabulariesAsync(array $args = [])
 * @method \Aws\Result listEvaluationFormVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEvaluationFormVersionsAsync(array $args = [])
 * @method \Aws\Result listEvaluationForms(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listEvaluationFormsAsync(array $args = [])
 * @method \Aws\Result listFlowAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listFlowAssociationsAsync(array $args = [])
 * @method \Aws\Result listHoursOfOperations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listHoursOfOperationsAsync(array $args = [])
 * @method \Aws\Result listInstanceAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInstanceAttributesAsync(array $args = [])
 * @method \Aws\Result listInstanceStorageConfigs(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInstanceStorageConfigsAsync(array $args = [])
 * @method \Aws\Result listInstances(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listInstancesAsync(array $args = [])
 * @method \Aws\Result listIntegrationAssociations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listIntegrationAssociationsAsync(array $args = [])
 * @method \Aws\Result listLambdaFunctions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLambdaFunctionsAsync(array $args = [])
 * @method \Aws\Result listLexBots(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listLexBotsAsync(array $args = [])
 * @method \Aws\Result listPhoneNumbers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPhoneNumbersAsync(array $args = [])
 * @method \Aws\Result listPhoneNumbersV2(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPhoneNumbersV2Async(array $args = [])
 * @method \Aws\Result listPredefinedAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPredefinedAttributesAsync(array $args = [])
 * @method \Aws\Result listPrompts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listPromptsAsync(array $args = [])
 * @method \Aws\Result listQueueQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQueueQuickConnectsAsync(array $args = [])
 * @method \Aws\Result listQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQueuesAsync(array $args = [])
 * @method \Aws\Result listQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listQuickConnectsAsync(array $args = [])
 * @method \Aws\Result listRealtimeContactAnalysisSegmentsV2(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRealtimeContactAnalysisSegmentsV2Async(array $args = [])
 * @method \Aws\Result listRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result listRoutingProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRoutingProfilesAsync(array $args = [])
 * @method \Aws\Result listRules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listRulesAsync(array $args = [])
 * @method \Aws\Result listSecurityKeys(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSecurityKeysAsync(array $args = [])
 * @method \Aws\Result listSecurityProfileApplications(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSecurityProfileApplicationsAsync(array $args = [])
 * @method \Aws\Result listSecurityProfilePermissions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSecurityProfilePermissionsAsync(array $args = [])
 * @method \Aws\Result listSecurityProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listSecurityProfilesAsync(array $args = [])
 * @method \Aws\Result listTagsForResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTagsForResourceAsync(array $args = [])
 * @method \Aws\Result listTaskTemplates(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTaskTemplatesAsync(array $args = [])
 * @method \Aws\Result listTrafficDistributionGroupUsers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficDistributionGroupUsersAsync(array $args = [])
 * @method \Aws\Result listTrafficDistributionGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listTrafficDistributionGroupsAsync(array $args = [])
 * @method \Aws\Result listUseCases(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUseCasesAsync(array $args = [])
 * @method \Aws\Result listUserHierarchyGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUserHierarchyGroupsAsync(array $args = [])
 * @method \Aws\Result listUserProficiencies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUserProficienciesAsync(array $args = [])
 * @method \Aws\Result listUsers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listUsersAsync(array $args = [])
 * @method \Aws\Result listViewVersions(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listViewVersionsAsync(array $args = [])
 * @method \Aws\Result listViews(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listViewsAsync(array $args = [])
 * @method \Aws\Result monitorContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise monitorContactAsync(array $args = [])
 * @method \Aws\Result pauseContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise pauseContactAsync(array $args = [])
 * @method \Aws\Result putUserStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise putUserStatusAsync(array $args = [])
 * @method \Aws\Result releasePhoneNumber(array $args = [])
 * @method \GuzzleHttp\Promise\Promise releasePhoneNumberAsync(array $args = [])
 * @method \Aws\Result replicateInstance(array $args = [])
 * @method \GuzzleHttp\Promise\Promise replicateInstanceAsync(array $args = [])
 * @method \Aws\Result resumeContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise resumeContactAsync(array $args = [])
 * @method \Aws\Result resumeContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise resumeContactRecordingAsync(array $args = [])
 * @method \Aws\Result searchAgentStatuses(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchAgentStatusesAsync(array $args = [])
 * @method \Aws\Result searchAvailablePhoneNumbers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchAvailablePhoneNumbersAsync(array $args = [])
 * @method \Aws\Result searchContactFlowModules(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchContactFlowModulesAsync(array $args = [])
 * @method \Aws\Result searchContactFlows(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchContactFlowsAsync(array $args = [])
 * @method \Aws\Result searchContacts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchContactsAsync(array $args = [])
 * @method \Aws\Result searchEmailAddresses(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchEmailAddressesAsync(array $args = [])
 * @method \Aws\Result searchHoursOfOperations(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchHoursOfOperationsAsync(array $args = [])
 * @method \Aws\Result searchPredefinedAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchPredefinedAttributesAsync(array $args = [])
 * @method \Aws\Result searchPrompts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchPromptsAsync(array $args = [])
 * @method \Aws\Result searchQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchQueuesAsync(array $args = [])
 * @method \Aws\Result searchQuickConnects(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchQuickConnectsAsync(array $args = [])
 * @method \Aws\Result searchResourceTags(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchResourceTagsAsync(array $args = [])
 * @method \Aws\Result searchRoutingProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchRoutingProfilesAsync(array $args = [])
 * @method \Aws\Result searchSecurityProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchSecurityProfilesAsync(array $args = [])
 * @method \Aws\Result searchUserHierarchyGroups(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchUserHierarchyGroupsAsync(array $args = [])
 * @method \Aws\Result searchUsers(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchUsersAsync(array $args = [])
 * @method \Aws\Result searchVocabularies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise searchVocabulariesAsync(array $args = [])
 * @method \Aws\Result sendChatIntegrationEvent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise sendChatIntegrationEventAsync(array $args = [])
 * @method \Aws\Result sendOutboundEmail(array $args = [])
 * @method \GuzzleHttp\Promise\Promise sendOutboundEmailAsync(array $args = [])
 * @method \Aws\Result startAttachedFileUpload(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startAttachedFileUploadAsync(array $args = [])
 * @method \Aws\Result startChatContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startChatContactAsync(array $args = [])
 * @method \Aws\Result startContactEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startContactEvaluationAsync(array $args = [])
 * @method \Aws\Result startContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startContactRecordingAsync(array $args = [])
 * @method \Aws\Result startContactStreaming(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startContactStreamingAsync(array $args = [])
 * @method \Aws\Result startEmailContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startEmailContactAsync(array $args = [])
 * @method \Aws\Result startOutboundChatContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startOutboundChatContactAsync(array $args = [])
 * @method \Aws\Result startOutboundEmailContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startOutboundEmailContactAsync(array $args = [])
 * @method \Aws\Result startOutboundVoiceContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startOutboundVoiceContactAsync(array $args = [])
 * @method \Aws\Result startScreenSharing(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startScreenSharingAsync(array $args = [])
 * @method \Aws\Result startTaskContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startTaskContactAsync(array $args = [])
 * @method \Aws\Result startWebRTCContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startWebRTCContactAsync(array $args = [])
 * @method \Aws\Result stopContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopContactAsync(array $args = [])
 * @method \Aws\Result stopContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopContactRecordingAsync(array $args = [])
 * @method \Aws\Result stopContactStreaming(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopContactStreamingAsync(array $args = [])
 * @method \Aws\Result submitContactEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise submitContactEvaluationAsync(array $args = [])
 * @method \Aws\Result suspendContactRecording(array $args = [])
 * @method \GuzzleHttp\Promise\Promise suspendContactRecordingAsync(array $args = [])
 * @method \Aws\Result tagContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagContactAsync(array $args = [])
 * @method \Aws\Result tagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise tagResourceAsync(array $args = [])
 * @method \Aws\Result transferContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise transferContactAsync(array $args = [])
 * @method \Aws\Result untagContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagContactAsync(array $args = [])
 * @method \Aws\Result untagResource(array $args = [])
 * @method \GuzzleHttp\Promise\Promise untagResourceAsync(array $args = [])
 * @method \Aws\Result updateAgentStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAgentStatusAsync(array $args = [])
 * @method \Aws\Result updateAuthenticationProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateAuthenticationProfileAsync(array $args = [])
 * @method \Aws\Result updateContact(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactAsync(array $args = [])
 * @method \Aws\Result updateContactAttributes(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactAttributesAsync(array $args = [])
 * @method \Aws\Result updateContactEvaluation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactEvaluationAsync(array $args = [])
 * @method \Aws\Result updateContactFlowContent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowContentAsync(array $args = [])
 * @method \Aws\Result updateContactFlowMetadata(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowMetadataAsync(array $args = [])
 * @method \Aws\Result updateContactFlowModuleContent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowModuleContentAsync(array $args = [])
 * @method \Aws\Result updateContactFlowModuleMetadata(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowModuleMetadataAsync(array $args = [])
 * @method \Aws\Result updateContactFlowName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactFlowNameAsync(array $args = [])
 * @method \Aws\Result updateContactRoutingData(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactRoutingDataAsync(array $args = [])
 * @method \Aws\Result updateContactSchedule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateContactScheduleAsync(array $args = [])
 * @method \Aws\Result updateEmailAddressMetadata(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEmailAddressMetadataAsync(array $args = [])
 * @method \Aws\Result updateEvaluationForm(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateEvaluationFormAsync(array $args = [])
 * @method \Aws\Result updateHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result updateInstanceAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateInstanceAttributeAsync(array $args = [])
 * @method \Aws\Result updateInstanceStorageConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateInstanceStorageConfigAsync(array $args = [])
 * @method \Aws\Result updateParticipantRoleConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateParticipantRoleConfigAsync(array $args = [])
 * @method \Aws\Result updatePhoneNumber(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePhoneNumberAsync(array $args = [])
 * @method \Aws\Result updatePhoneNumberMetadata(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePhoneNumberMetadataAsync(array $args = [])
 * @method \Aws\Result updatePredefinedAttribute(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePredefinedAttributeAsync(array $args = [])
 * @method \Aws\Result updatePrompt(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updatePromptAsync(array $args = [])
 * @method \Aws\Result updateQueueHoursOfOperation(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueHoursOfOperationAsync(array $args = [])
 * @method \Aws\Result updateQueueMaxContacts(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueMaxContactsAsync(array $args = [])
 * @method \Aws\Result updateQueueName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueNameAsync(array $args = [])
 * @method \Aws\Result updateQueueOutboundCallerConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueOutboundCallerConfigAsync(array $args = [])
 * @method \Aws\Result updateQueueOutboundEmailConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueOutboundEmailConfigAsync(array $args = [])
 * @method \Aws\Result updateQueueStatus(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQueueStatusAsync(array $args = [])
 * @method \Aws\Result updateQuickConnectConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQuickConnectConfigAsync(array $args = [])
 * @method \Aws\Result updateQuickConnectName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateQuickConnectNameAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileAgentAvailabilityTimer(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileAgentAvailabilityTimerAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileConcurrency(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileConcurrencyAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileDefaultOutboundQueue(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileDefaultOutboundQueueAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileNameAsync(array $args = [])
 * @method \Aws\Result updateRoutingProfileQueues(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRoutingProfileQueuesAsync(array $args = [])
 * @method \Aws\Result updateRule(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateRuleAsync(array $args = [])
 * @method \Aws\Result updateSecurityProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateSecurityProfileAsync(array $args = [])
 * @method \Aws\Result updateTaskTemplate(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateTaskTemplateAsync(array $args = [])
 * @method \Aws\Result updateTrafficDistribution(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateTrafficDistributionAsync(array $args = [])
 * @method \Aws\Result updateUserHierarchy(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserHierarchyAsync(array $args = [])
 * @method \Aws\Result updateUserHierarchyGroupName(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserHierarchyGroupNameAsync(array $args = [])
 * @method \Aws\Result updateUserHierarchyStructure(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserHierarchyStructureAsync(array $args = [])
 * @method \Aws\Result updateUserIdentityInfo(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserIdentityInfoAsync(array $args = [])
 * @method \Aws\Result updateUserPhoneConfig(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserPhoneConfigAsync(array $args = [])
 * @method \Aws\Result updateUserProficiencies(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserProficienciesAsync(array $args = [])
 * @method \Aws\Result updateUserRoutingProfile(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserRoutingProfileAsync(array $args = [])
 * @method \Aws\Result updateUserSecurityProfiles(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateUserSecurityProfilesAsync(array $args = [])
 * @method \Aws\Result updateViewContent(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateViewContentAsync(array $args = [])
 * @method \Aws\Result updateViewMetadata(array $args = [])
 * @method \GuzzleHttp\Promise\Promise updateViewMetadataAsync(array $args = [])
 */
class ConnectClient extends AwsClient {}
